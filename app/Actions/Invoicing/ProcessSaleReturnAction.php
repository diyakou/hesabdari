<?php

namespace App\Actions\Invoicing;

use App\Actions\Accounting\RecordJournalEntryAction;
use App\Actions\Inventory\UpdateStockAction;
use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\DeviceMovement;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessSaleReturnAction
{
    public function __construct(
        private readonly UpdateStockAction $updateStockAction,
        private readonly RecordJournalEntryAction $recordJournalEntryAction,
    ) {}

    /**
     * @param array{
     *     reference_invoice_id: int,
     *     reference_line_id: int,
     *     quantity: int,
     *     notes?: string|null
     * } $data
     */
    public function execute(array $data, ?User $actor = null): Invoice
    {
        return DB::transaction(function () use ($data, $actor) {
            $originalLine = InvoiceLine::with(['invoice', 'product', 'variant', 'device'])
                ->where('id', $data['reference_line_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $originalInvoice = $originalLine->invoice;

            if ($originalInvoice->id !== (int) $data['reference_invoice_id'] || ! $originalInvoice->isSale() || ! $originalInvoice->isFinalized()) {
                throw ValidationException::withMessages([
                    'reference_invoice_id' => 'فاکتور مرجع نامعتبر یا نهایی‌نشده است.',
                ]);
            }

            // Check previous returns for this line
            $alreadyReturnedQty = (int) InvoiceLine::where('reference_line_id', $originalLine->id)
                ->whereHas('invoice', fn ($q) => $q->where('type', 'sale_return')->where('status', 'finalized'))
                ->sum('quantity');

            $returnQty = (int) $data['quantity'];
            $maxReturnable = $originalLine->quantity - $alreadyReturnedQty;

            if ($returnQty <= 0 || $returnQty > $maxReturnable) {
                throw ValidationException::withMessages([
                    'quantity' => "تعداد مرجوعی نامعتبر است. حداکثر مقدار قابل برگشت برای این ردیف: {$maxReturnable}",
                ]);
            }

            // Net amount per unit in original line
            $originalNetLineTotal = ($originalLine->quantity * $originalLine->unit_price_rials) - $originalLine->discount_rials;
            $unitReturnAmount = (int) floor($originalNetLineTotal / $originalLine->quantity);

            // Remainder handling if returning the last of the quantity
            if ($alreadyReturnedQty + $returnQty === $originalLine->quantity) {
                $alreadyReturnedAmount = (int) InvoiceLine::where('reference_line_id', $originalLine->id)
                    ->whereHas('invoice', fn ($q) => $q->where('type', 'sale_return')->where('status', 'finalized'))
                    ->get()
                    ->sum(fn ($l) => ($l->quantity * $l->unit_price_rials) - $l->discount_rials);

                $totalReturnAmount = $originalNetLineTotal - $alreadyReturnedAmount;
            } else {
                $totalReturnAmount = $unitReturnAmount * $returnQty;
            }

            // Create return invoice
            $countToday = Invoice::whereDate('created_at', now()->toDateString())->count();
            $invoiceNumber = 'RET-' . now()->format('Ymd') . '-' . str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

            $returnInvoice = Invoice::create([
                'type' => 'sale_return',
                'invoice_number' => $invoiceNumber,
                'party_id' => $originalInvoice->party_id,
                'warehouse_id' => $originalInvoice->warehouse_id,
                'issue_date' => now()->toDateString(),
                'status' => 'finalized',
                'subtotal_rials' => $totalReturnAmount,
                'discount_rials' => 0,
                'total_amount_rials' => $totalReturnAmount,
                'reference_invoice_id' => $originalInvoice->id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id ?? 1,
            ]);

            $snapshotCost = $originalLine->snapshot_cost_rials;
            $totalCostToReverse = $snapshotCost * $returnQty;

            $returnLine = InvoiceLine::create([
                'invoice_id' => $returnInvoice->id,
                'product_id' => $originalLine->product_id,
                'product_variant_id' => $originalLine->product_variant_id,
                'device_id' => $originalLine->device_id,
                'quantity' => $returnQty,
                'unit_price_rials' => (int) floor($totalReturnAmount / $returnQty),
                'discount_rials' => 0,
                'snapshot_cost_rials' => $snapshotCost,
                'reference_line_id' => $originalLine->id,
            ]);

            // Handle inventory reinstatement
            if ($originalLine->product->isSerialized() && $originalLine->device) {
                $device = Device::where('id', $originalLine->device_id)->lockForUpdate()->firstOrFail();

                // Returned phone moves to QUARANTINE status
                $previousStatus = $device->operational_status->value;
                $device->operational_status = DeviceStatus::Quarantine;
                $device->warehouse_id = $originalInvoice->warehouse_id;
                $device->save();

                DeviceMovement::create([
                    'device_id' => $device->id,
                    'from_status' => $previousStatus,
                    'to_status' => DeviceStatus::Quarantine->value,
                    'from_warehouse_id' => null,
                    'to_warehouse_id' => $originalInvoice->warehouse_id,
                    'document_type' => Invoice::class,
                    'document_id' => $returnInvoice->id,
                    'notes' => "مرجوعی فروش به فاکتور {$returnInvoice->invoice_number} (انتقال به قرنطینه)",
                    'created_by' => $actor?->id,
                ]);
            } elseif ($originalLine->product->isStock()) {
                // Stock item: increase back with original snapshot cost
                $this->updateStockAction->increase(
                    $originalLine->product_variant_id,
                    $originalInvoice->warehouse_id,
                    $returnQty,
                    $totalCostToReverse,
                    Invoice::class,
                    $returnInvoice->id,
                    "برگشت از فروش به فاکتور {$returnInvoice->invoice_number}",
                    $actor?->id,
                );
            }

            // Balanced Double-Entry:
            // 1. Sales Return Revenue reversal:
            // Debit: 403 (Sales Returns)
            // Credit: 102 (Accounts Receivable) with party_id (creating credit for customer)
            $this->recordJournalEntryAction->execute([
                'date' => $returnInvoice->issue_date,
                'description' => "برگشت از فروش فاکتور {$returnInvoice->invoice_number}",
                'source_type' => Invoice::class,
                'source_id' => $returnInvoice->id,
                'lines' => [
                    [
                        'account_code' => '403', // Sales Returns
                        'debit_rials' => $totalReturnAmount,
                        'credit_rials' => 0,
                        'notes' => 'برگشت از فروش',
                    ],
                    [
                        'account_code' => '102', // Accounts Receivable
                        'party_id' => $returnInvoice->party_id,
                        'debit_rials' => 0,
                        'credit_rials' => $totalReturnAmount,
                        'notes' => "بستانکاری مشتری بابت مرجوعی {$returnInvoice->invoice_number}",
                    ],
                ],
            ]);

            // 2. COGS reversal (for goods):
            // Debit: 103 (Merchandise Inventory)
            // Credit: 501 (Cost of Goods Sold)
            if ($totalCostToReverse > 0) {
                $this->recordJournalEntryAction->execute([
                    'date' => $returnInvoice->issue_date,
                    'description' => "برگشت بهای کالای مرجوعی فاکتور {$returnInvoice->invoice_number}",
                    'source_type' => Invoice::class . ':return_cogs',
                    'source_id' => $returnInvoice->id,
                    'lines' => [
                        [
                            'account_code' => '103', // Merchandise Inventory
                            'debit_rials' => $totalCostToReverse,
                            'credit_rials' => 0,
                            'notes' => 'برگشت بهای موجودی کالا',
                        ],
                        [
                            'account_code' => '501', // COGS
                            'debit_rials' => 0,
                            'credit_rials' => $totalCostToReverse,
                            'notes' => 'کاهش بهای تمام‌شده',
                        ],
                    ],
                ]);
            }

            return $returnInvoice->load(['lines.variant.product', 'lines.device', 'party']);
        });
    }
}

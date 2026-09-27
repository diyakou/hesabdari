<?php

namespace App\Actions\Purchases;

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

class FinalizePurchaseAction
{
    public function __construct(
        private readonly UpdateStockAction $updateStockAction,
        private readonly RecordJournalEntryAction $recordJournalEntryAction,
    ) {}

    public function execute(Invoice $invoice, ?User $actor = null): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'فقط فاکتورهای در وضعیت پیش‌نویس قابل نهایی‌سازی هستند.',
            ]);
        }

        if (! $invoice->isPurchase()) {
            throw ValidationException::withMessages([
                'type' => 'این فاکتور از نوع خرید نیست.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $actor) {
            $lockedInvoice = Invoice::where('id', $invoice->id)
                ->with(['lines.product', 'lines.variant', 'party'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInvoice->status !== 'draft') {
                return $lockedInvoice;
            }

            if ($lockedInvoice->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => 'فاکتور خرید فاقد هرگونه ردیف کالا است.',
                ]);
            }

            // Check allocation basis for landed cost
            $totalNetLines = 0;
            foreach ($lockedInvoice->lines as $line) {
                $net = ($line->quantity * $line->unit_price_rials) - $line->discount_rials;
                $totalNetLines += $net;
            }

            $additionalCost = $lockedInvoice->additional_cost_rials;

            if ($additionalCost > 0 && $totalNetLines <= 0) {
                throw ValidationException::withMessages([
                    'additional_cost' => 'مبنای تخصیص هزینه جانبی خرید صفر است و امکان سرشکن کردن وجود ندارد.',
                ]);
            }

            // Distribute landed cost proportionally
            $allocatedSoFar = 0;
            $lineCount = $lockedInvoice->lines->count();

            foreach ($lockedInvoice->lines as $index => $line) {
                $net = ($line->quantity * $line->unit_price_rials) - $line->discount_rials;

                if ($additionalCost > 0 && $totalNetLines > 0) {
                    if ($index === $lineCount - 1) {
                        // Remainder rule: last line takes all remainder
                        $lineAllocated = $additionalCost - $allocatedSoFar;
                    } else {
                        $lineAllocated = (int) floor(($net / $totalNetLines) * $additionalCost);
                        $allocatedSoFar += $lineAllocated;
                    }
                } else {
                    $lineAllocated = 0;
                }

                $line->allocated_cost_rials = $lineAllocated;
                $totalLineCost = $net + $lineAllocated;
                $unitCost = (int) floor($totalLineCost / $line->quantity);
                $line->snapshot_cost_rials = $unitCost;
                $line->save();

                // Update inventory based on product type
                if ($line->product->isSerialized()) {
                    // Serialized item: requires device_id, qty must be 1
                    if ($line->quantity !== 1) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.quantity" => 'برای کالای سریالی/گوشی تعداد ردیف باید دقیقاً ۱ باشد.',
                        ]);
                    }

                    if (! $line->device_id) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.device_id" => 'دستگاه فیزیکی برای ردیف کالای سریالی تعیین نشده است.',
                        ]);
                    }

                    $device = Device::where('id', $line->device_id)->lockForUpdate()->firstOrFail();
                    $previousStatus = $device->operational_status->value;
                    $device->operational_status = DeviceStatus::Available;
                    $device->warehouse_id = $lockedInvoice->warehouse_id;
                    $device->save();

                    DeviceMovement::create([
                        'device_id' => $device->id,
                        'from_status' => $previousStatus,
                        'to_status' => DeviceStatus::Available->value,
                        'from_warehouse_id' => null,
                        'to_warehouse_id' => $lockedInvoice->warehouse_id,
                        'document_type' => Invoice::class,
                        'document_id' => $lockedInvoice->id,
                        'notes' => "ورود ناشی از فاکتور خرید {$lockedInvoice->invoice_number}",
                        'created_by' => $actor?->id,
                    ]);
                } elseif ($line->product->isStock()) {
                    // Stock item (accessories): moving weighted average
                    $this->updateStockAction->increase(
                        $line->product_variant_id,
                        $lockedInvoice->warehouse_id,
                        $line->quantity,
                        $totalLineCost,
                        Invoice::class,
                        $lockedInvoice->id,
                        "ورود ناشی از فاکتور خرید {$lockedInvoice->invoice_number}",
                        $actor?->id,
                    );
                }
            }

            // Total amount = subtotal - discount + additional_cost
            $finalTotalAmount = ($lockedInvoice->subtotal_rials - $lockedInvoice->discount_rials) + $additionalCost;
            $lockedInvoice->total_amount_rials = $finalTotalAmount;
            $lockedInvoice->status = 'finalized';
            $lockedInvoice->save();

            // Record balanced double-entry GL entry:
            // Debit: 103 (Merchandise Inventory) with total inventory cost
            // Credit: 201 (Accounts Payable) with total amount to supplier
            $this->recordJournalEntryAction->execute([
                'date' => $lockedInvoice->issue_date,
                'description' => "ثبت نهایی فاکتور خرید {$lockedInvoice->invoice_number}",
                'source_type' => Invoice::class,
                'source_id' => $lockedInvoice->id,
                'lines' => [
                    [
                        'account_code' => '103', // Merchandise Inventory
                        'debit_rials' => $finalTotalAmount,
                        'credit_rials' => 0,
                        'notes' => 'ورود کالا به انبار',
                    ],
                    [
                        'account_code' => '201', // Accounts Payable
                        'party_id' => $lockedInvoice->party_id,
                        'debit_rials' => 0,
                        'credit_rials' => $finalTotalAmount,
                        'notes' => "بستانکاری تأمین‌کننده بابت فاکتور خرید {$lockedInvoice->invoice_number}",
                    ],
                ],
            ]);

            return $lockedInvoice->load(['lines.variant.product', 'party']);
        });
    }
}

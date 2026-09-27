<?php

namespace App\Actions\Invoicing;

use App\Actions\Accounting\RecordJournalEntryAction;
use App\Actions\Inventory\UpdateStockAction;
use App\Actions\Payments\RecordPaymentAction;
use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\DeviceMovement;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeSaleAction
{
    public function __construct(
        private readonly UpdateStockAction $updateStockAction,
        private readonly RecordJournalEntryAction $recordJournalEntryAction,
        private readonly RecordPaymentAction $recordPaymentAction,
    ) {}

    /**
     * Finalizes a sale invoice atomically.
     *
     * @param array<int, array{financial_account_id: int, payment_method: string, amount_rials: int}> $payments
     */
    public function execute(Invoice $invoice, array $payments = [], ?User $actor = null): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'فقط فاکتورهای پیش‌نویس قابل نهایی‌سازی هستند.',
            ]);
        }

        if (! $invoice->isSale()) {
            throw ValidationException::withMessages([
                'type' => 'این فاکتور از نوع فروش نیست.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $payments, $actor) {
            $lockedInvoice = Invoice::where('id', $invoice->id)
                ->with(['lines.product', 'lines.variant', 'party'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedInvoice->status !== 'draft') {
                return $lockedInvoice;
            }

            if ($lockedInvoice->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => 'فاکتور فروش فاقد ردیف است.',
                ]);
            }

            // Preserve explicit line discounts, then distribute the invoice-level
            // discount across the remaining value of each line.
            $grossSubtotal = 0;
            $discountableSubtotal = 0;
            foreach ($lockedInvoice->lines as $line) {
                $lineGross = $line->quantity * $line->unit_price_rials;
                $lineDiscount = $line->discount_rials;

                if ($lineDiscount < 0 || $lineDiscount > $lineGross) {
                    throw ValidationException::withMessages([
                        'lines' => 'تخفیف یکی از ردیف‌های فاکتور نامعتبر است.',
                    ]);
                }

                $grossSubtotal += $lineGross;
                $discountableSubtotal += $lineGross - $lineDiscount;
            }

            $totalInvoiceDiscount = $lockedInvoice->discount_rials;
            if ($totalInvoiceDiscount > $discountableSubtotal) {
                throw ValidationException::withMessages([
                    'discount_toman' => 'تخفیف کل فاکتور از مبلغ باقی‌مانده پس از تخفیف ردیف‌ها بیشتر است.',
                ]);
            }

            $lineCount = $lockedInvoice->lines->count();
            $allocatedDiscountSoFar = 0;

            $totalGoodsRevenue = 0;
            $totalServiceRevenue = 0;
            $totalCostOfGoodsSold = 0;

            foreach ($lockedInvoice->lines as $index => $line) {
                $lineGross = $line->quantity * $line->unit_price_rials;

                $lineDiscount = $line->discount_rials;
                $lineAmountAfterLineDiscount = $lineGross - $lineDiscount;

                if ($totalInvoiceDiscount > 0 && $discountableSubtotal > 0) {
                    if ($index === $lineCount - 1) {
                        $allocatedInvoiceDiscount = $totalInvoiceDiscount - $allocatedDiscountSoFar;
                    } else {
                        $allocatedInvoiceDiscount = intdiv($lineAmountAfterLineDiscount * $totalInvoiceDiscount, $discountableSubtotal);
                        $allocatedDiscountSoFar += $allocatedInvoiceDiscount;
                    }

                    $lineDiscount += $allocatedInvoiceDiscount;
                }

                $line->discount_rials = $lineDiscount;
                $netLineAmount = $lineGross - $lineDiscount;
                $effectiveUnitPrice = (int) floor($netLineAmount / $line->quantity);

                // Minimum selling price check
                if ($line->variant && $effectiveUnitPrice < $line->variant->min_selling_price_rials) {
                    // Check if actor is manager, otherwise reject
                    if (! $actor || ! $actor->isManager()) {
                        $minToman = number_format($line->variant->min_selling_price_rials / 10);
                        throw ValidationException::withMessages([
                            "lines.{$index}" => "قیمت نهایی ردیف {$line->product->name} کمتر از حداقل مجاز ({$minToman} تومان) است و نیاز به تأیید مدیر دارد.",
                        ]);
                    }
                }

                // Inventory and Costing deduction per product type
                if ($line->product->isSerialized()) {
                    // Serialized Phone
                    if ($line->quantity !== 1) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.quantity" => 'تعداد ردیف گوشی سریالی باید دقیقاً ۱ باشد.',
                        ]);
                    }

                    if (! $line->device_id) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.device_id" => 'انتخاب دستگاه مشخص برای گوشی الزامی است.',
                        ]);
                    }

                    $device = Device::where('id', $line->device_id)->lockForUpdate()->firstOrFail();

                    if ($device->operational_status !== DeviceStatus::Available) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.device_id" => "گوشی با شناسه {$device->primary_imei} در وضعیت موجود برای فروش نیست (وضعیت: {$device->operational_status->label()}).",
                        ]);
                    }

                    // Find device's purchase snapshot cost from previous purchase or 0
                    $purchaseLine = InvoiceLine::where('device_id', $device->id)
                        ->whereHas('invoice', fn ($q) => $q->where('type', 'purchase')->where('status', 'finalized'))
                        ->latest('id')
                        ->first();

                    $deviceCost = $purchaseLine ? $purchaseLine->snapshot_cost_rials : 0;

                    $line->snapshot_cost_rials = $deviceCost;
                    $line->save();

                    // Transition device status to 'sold'
                    $device->operational_status = DeviceStatus::Sold;
                    $device->save();

                    DeviceMovement::create([
                        'device_id' => $device->id,
                        'from_status' => DeviceStatus::Available->value,
                        'to_status' => DeviceStatus::Sold->value,
                        'from_warehouse_id' => $lockedInvoice->warehouse_id,
                        'to_warehouse_id' => null,
                        'document_type' => Invoice::class,
                        'document_id' => $lockedInvoice->id,
                        'notes' => "فروش به فاکتور {$lockedInvoice->invoice_number}",
                        'created_by' => $actor?->id,
                    ]);

                    $totalGoodsRevenue += $netLineAmount;
                    $totalCostOfGoodsSold += $deviceCost;
                } elseif ($line->product->isStock()) {
                    // Accessories: moving average stock decrease
                    $exit = $this->updateStockAction->decrease(
                        $line->product_variant_id,
                        $lockedInvoice->warehouse_id,
                        $line->quantity,
                        Invoice::class,
                        $lockedInvoice->id,
                        "فروش به فاکتور {$lockedInvoice->invoice_number}",
                        $actor?->id,
                    );

                    $line->snapshot_cost_rials = $exit['unit_cost_rials'];
                    $line->save();

                    $totalGoodsRevenue += $netLineAmount;
                    $totalCostOfGoodsSold += $exit['total_cost_rials'];
                } elseif ($line->product->isService()) {
                    // Service: no inventory movement, snapshot cost is direct cost if any
                    $line->snapshot_cost_rials = 0;
                    $line->save();

                    $totalServiceRevenue += $netLineAmount;
                }
            }

            $finalInvoiceTotal = $totalGoodsRevenue + $totalServiceRevenue;
            $lockedInvoice->subtotal_rials = $grossSubtotal;
            $lockedInvoice->total_amount_rials = $finalInvoiceTotal;
            $lockedInvoice->status = 'finalized';
            $lockedInvoice->save();

            // 1. Double-Entry Revenue:
            // Debit: 102 (Accounts Receivable) with party_id for total invoice amount
            // Credit: 401 (Merchandise Sales) for goods revenue
            // Credit: 402 (Service Revenue) for service revenue
            $revenueLines = [
                [
                    'account_code' => '102', // Accounts Receivable
                    'party_id' => $lockedInvoice->party_id,
                    'debit_rials' => $finalInvoiceTotal,
                    'credit_rials' => 0,
                    'notes' => "بدهکاری مشتری بابت فاکتور فروش {$lockedInvoice->invoice_number}",
                ],
            ];

            if ($totalGoodsRevenue > 0) {
                $revenueLines[] = [
                    'account_code' => '401', // Merchandise Sales
                    'debit_rials' => 0,
                    'credit_rials' => $totalGoodsRevenue,
                    'notes' => 'درآمد فروش کالا و گوشی',
                ];
            }

            if ($totalServiceRevenue > 0) {
                $revenueLines[] = [
                    'account_code' => '402', // Service Revenue
                    'debit_rials' => 0,
                    'credit_rials' => $totalServiceRevenue,
                    'notes' => 'درآمد خدمات',
                ];
            }

            $this->recordJournalEntryAction->execute([
                'date' => $lockedInvoice->issue_date,
                'description' => "ثبت نهایی فروش {$lockedInvoice->invoice_number}",
                'source_type' => Invoice::class,
                'source_id' => $lockedInvoice->id,
                'lines' => $revenueLines,
            ]);

            // 2. Double-Entry COGS:
            // Debit: 501 (Cost of Goods Sold)
            // Credit: 103 (Merchandise Inventory)
            if ($totalCostOfGoodsSold > 0) {
                $this->recordJournalEntryAction->execute([
                    'date' => $lockedInvoice->issue_date,
                    'description' => "بهای تمام‌شده کالای فروش‌رفته فاکتور {$lockedInvoice->invoice_number}",
                    'source_type' => Invoice::class . ':cogs',
                    'source_id' => $lockedInvoice->id,
                    'lines' => [
                        [
                            'account_code' => '501', // COGS
                            'debit_rials' => $totalCostOfGoodsSold,
                            'credit_rials' => 0,
                            'notes' => 'بهای تمام‌شده خروج کالا',
                        ],
                        [
                            'account_code' => '103', // Merchandise Inventory
                            'debit_rials' => 0,
                            'credit_rials' => $totalCostOfGoodsSold,
                            'notes' => 'کاهش موجودی انبار',
                        ],
                    ],
                ]);
            }

            // 3. Process immediate multi-tender payments if provided
            foreach ($payments as $paymentData) {
                $payAmount = (int) ($paymentData['amount_rials'] ?? 0);
                if ($payAmount > 0) {
                    $this->recordPaymentAction->execute([
                        'type' => 'receipt',
                        'party_id' => $lockedInvoice->party_id,
                        'financial_account_id' => (int) $paymentData['financial_account_id'],
                        'amount_rials' => $payAmount,
                        'payment_method' => $paymentData['payment_method'] ?? 'cash',
                        'date' => $lockedInvoice->issue_date,
                        'notes' => "تسویه هم‌زمان فاکتور فروش {$lockedInvoice->invoice_number}",
                        'allocations' => [
                            ['invoice_id' => $lockedInvoice->id, 'amount_rials' => $payAmount],
                        ],
                    ], $actor);
                }
            }

            return $lockedInvoice->load(['lines.variant.product', 'lines.device.identifiers', 'party']);
        });
    }
}

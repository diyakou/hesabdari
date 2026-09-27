<?php

namespace App\Actions\Inventory;

use App\Actions\Accounting\RecordJournalEntryAction;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustStockAction
{
    public function __construct(
        private readonly UpdateStockAction $updateStockAction,
        private readonly RecordJournalEntryAction $recordJournalEntryAction,
    ) {}

    /**
     * @param array{
     *     product_variant_id: int,
     *     warehouse_id: int,
     *     quantity_change: int, // positive for surplus, negative for shortage
     *     unit_cost_rials?: int,
     *     reason: string
     * } $data
     */
    public function execute(array $data, User $actor): StockBalance
    {
        // Must be authorized (Manager)
        if (! $actor->isManager()) {
            throw ValidationException::withMessages([
                'actor' => 'تأیید نهایی تعدیل موجودی انبار فقط توسط مدیر مجاز است.',
            ]);
        }

        $change = (int) ($data['quantity_change'] ?? 0);
        if ($change === 0) {
            throw ValidationException::withMessages([
                'quantity_change' => 'میزان تعدیل موجودی نمی‌تواند صفر باشد.',
            ]);
        }

        if (empty($data['reason'])) {
            throw ValidationException::withMessages([
                'reason' => 'ثبت دلیل و مستند تعدیل موجودی الزامی است.',
            ]);
        }

        return DB::transaction(function () use ($data, $change, $actor) {
            $variant = ProductVariant::with('product')->findOrFail($data['product_variant_id']);
            $warehouseId = (int) $data['warehouse_id'];
            $reason = trim($data['reason']);

            $stock = StockBalance::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if ($change > 0) {
                // Surplus in inventory (اضافات انبار)
                $unitCost = (int) ($data['unit_cost_rials'] ?? ($stock ? $stock->average_unit_cost : 0));
                if ($unitCost <= 0) {
                    $unitCost = (int) floor($variant->selling_price_rials * 0.7); // estimate fallback
                }
                $totalCost = $unitCost * $change;

                $this->updateStockAction->increase(
                    $variant->id,
                    $warehouseId,
                    $change,
                    $totalCost,
                    StockBalance::class,
                    $variant->id,
                    "تعدیل اضافات انبار: {$reason}",
                    $actor->id
                );

                // GL: Debit 103 (Merchandise Inventory), Credit 602 (Inventory Adjustment Gain)
                $this->recordJournalEntryAction->execute([
                    'date' => now()->toDateString(),
                    'description' => "تعدیل اضافات انبار {$variant->display_name}: {$reason}",
                    'source_type' => StockBalance::class . ':surplus',
                    'source_id' => $variant->id,
                    'lines' => [
                        [
                            'account_code' => '103',
                            'debit_rials' => $totalCost,
                            'credit_rials' => 0,
                            'notes' => 'افزایش ارزش موجودی',
                        ],
                        [
                            'account_code' => '602',
                            'debit_rials' => 0,
                            'credit_rials' => $totalCost,
                            'notes' => 'درآمد ناشی از اضافات انبار',
                        ],
                    ],
                ]);
            } else {
                // Shortage in inventory (کسری انبار)
                $qtyToDeduct = abs($change);
                $exit = $this->updateStockAction->decrease(
                    $variant->id,
                    $warehouseId,
                    $qtyToDeduct,
                    StockBalance::class,
                    $variant->id,
                    "تعدیل کسری انبار: {$reason}",
                    $actor->id
                );

                // GL: Debit 602 (Inventory Adjustment Expense), Credit 103 (Merchandise Inventory)
                $this->recordJournalEntryAction->execute([
                    'date' => now()->toDateString(),
                    'description' => "تعدیل کسری انبار {$variant->display_name}: {$reason}",
                    'source_type' => StockBalance::class . ':shortage',
                    'source_id' => $variant->id,
                    'lines' => [
                        [
                            'account_code' => '602',
                            'debit_rials' => $exit['total_cost_rials'],
                            'credit_rials' => 0,
                            'notes' => 'هزینه ناشی از کسری انبار',
                        ],
                        [
                            'account_code' => '103',
                            'debit_rials' => 0,
                            'credit_rials' => $exit['total_cost_rials'],
                            'notes' => 'کاهش ارزش موجودی کالا',
                        ],
                    ],
                ]);
            }

            return StockBalance::where('product_variant_id', $variant->id)
                ->where('warehouse_id', $warehouseId)
                ->firstOrFail();
        });
    }
}

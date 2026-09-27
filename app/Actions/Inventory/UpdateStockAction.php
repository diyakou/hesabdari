<?php

namespace App\Actions\Inventory;

use App\Models\InventoryMovement;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStockAction
{
    /**
     * Increase stock (e.g. Purchase, Inventory Adjustment In, Return).
     *
     * @return array{unit_cost_rials: int, total_cost_rials: int}
     */
    public function increase(
        int $productVariantId,
        int $warehouseId,
        int $quantity,
        int $totalCostRials,
        string $documentType,
        int $documentId,
        string $reason,
        ?int $userId = null,
    ): array {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'تعداد ورودی به انبار باید عدد مثبت باشد.',
            ]);
        }

        if ($totalCostRials < 0) {
            throw ValidationException::withMessages([
                'total_cost_rials' => 'ارزش کل کالای ورودی نمی‌تواند منفی باشد.',
            ]);
        }

        $unitCost = (int) floor($totalCostRials / $quantity);

        return DB::transaction(function () use (
            $productVariantId,
            $warehouseId,
            $quantity,
            $totalCostRials,
            $unitCost,
            $documentType,
            $documentId,
            $reason,
            $userId
        ) {
            $stock = StockBalance::where('product_variant_id', $productVariantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = StockBalance::create([
                    'product_variant_id' => $productVariantId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'total_cost_rials' => 0,
                ]);
            }

            $stock->quantity += $quantity;
            $stock->total_cost_rials += $totalCostRials;
            $stock->save();

            InventoryMovement::create([
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'quantity_change' => $quantity,
                'unit_cost_rials' => $unitCost,
                'total_cost_rials' => $totalCostRials,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            return [
                'unit_cost_rials' => $unitCost,
                'total_cost_rials' => $totalCostRials,
            ];
        });
    }

    /**
     * Decrease stock using moving weighted average (e.g. Sale, Inventory Adjustment Out, Return to Supplier).
     *
     * @return array{unit_cost_rials: int, total_cost_rials: int}
     */
    public function decrease(
        int $productVariantId,
        int $warehouseId,
        int $quantity,
        string $documentType,
        int $documentId,
        string $reason,
        ?int $userId = null,
    ): array {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'تعداد خروج از انبار باید عدد مثبت باشد.',
            ]);
        }

        return DB::transaction(function () use (
            $productVariantId,
            $warehouseId,
            $quantity,
            $documentType,
            $documentId,
            $reason,
            $userId
        ) {
            $stock = StockBalance::where('product_variant_id', $productVariantId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->quantity < $quantity) {
                $available = $stock ? $stock->quantity : 0;
                throw ValidationException::withMessages([
                    'quantity' => "موجودی انبار کافی نیست. موجودی فعلی: {$available}، تعداد درخواستی: {$quantity}",
                ]);
            }

            // Moving average unit cost
            $unitCost = (int) floor($stock->total_cost_rials / $stock->quantity);

            // Last unit exit rule: if exiting entire stock, deduct all remaining total cost
            if ($quantity === $stock->quantity) {
                $costDeducted = $stock->total_cost_rials;
            } else {
                $costDeducted = $unitCost * $quantity;
            }

            $stock->quantity -= $quantity;
            $stock->total_cost_rials -= $costDeducted;
            $stock->save();

            InventoryMovement::create([
                'product_variant_id' => $productVariantId,
                'warehouse_id' => $warehouseId,
                'quantity_change' => -$quantity,
                'unit_cost_rials' => $unitCost,
                'total_cost_rials' => $costDeducted,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            return [
                'unit_cost_rials' => $unitCost,
                'total_cost_rials' => $costDeducted,
            ];
        });
    }
}

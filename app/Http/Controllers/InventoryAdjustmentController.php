<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\AdjustStockAction;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryAdjustmentController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly AdjustStockAction $adjustStockAction,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user->is_active || $user->isSalesperson()) {
            abort(403, 'دسترسی غیرمجاز.');
        }

        $stocks = StockBalance::with(['productVariant.product', 'warehouse'])
            ->orderBy('product_variant_id')
            ->paginate(20);

        $adjustments = InventoryMovement::with(['productVariant.product', 'warehouse', 'creator'])
            ->where('document_type', StockBalance::class)
            ->latest()
            ->take(15)
            ->get();

        return view('inventory.index', compact('stocks', 'adjustments'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        if (! $user->isManager()) {
            abort(403, 'تأیید و اعمال تعدیل موجودی انبار منحصراً در اختیار مدیر فروشگاه است.');
        }

        $variants = ProductVariant::with('product')
            ->whereHas('product', fn ($q) => $q->where('type', 'stock'))
            ->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('inventory.adjust', compact('variants', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isManager()) {
            abort(403, 'تأیید و اعمال تعدیل موجودی انبار منحصراً در اختیار مدیر فروشگاه است.');
        }

        $validated = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity_change' => ['required', 'integer', 'not_in:0'],
            'unit_cost_toman' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $unitCostRials = ! empty($validated['unit_cost_toman'])
            ? (int) ($validated['unit_cost_toman'] * 10)
            : null;

        $stock = $this->adjustStockAction->execute([
            'product_variant_id' => (int) $validated['product_variant_id'],
            'warehouse_id' => (int) $validated['warehouse_id'],
            'quantity_change' => (int) $validated['quantity_change'],
            'unit_cost_rials' => $unitCostRials,
            'reason' => $validated['reason'],
        ], $user);

        $this->auditLogger->record(
            'inventory.adjusted',
            $stock,
            [],
            [
                'product_variant_id' => $stock->product_variant_id,
                'quantity_change' => (int) $validated['quantity_change'],
                'reason' => $validated['reason'],
            ],
            $user
        );

        return redirect()->route('inventory.index')
            ->with('status', 'تعدیل موجودی انبار با موفقیت اعمال و سند حسابداری مربوطه صادر شد.');
    }
}

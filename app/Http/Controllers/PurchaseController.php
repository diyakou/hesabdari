<?php

namespace App\Http\Controllers;

use App\Actions\Purchases\FinalizePurchaseAction;
use App\Enums\PartyRoleType;
use App\Models\Device;
use App\Models\Cheque;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Party;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchasePaymentPlan;
use App\Models\Warehouse;
use App\Services\Auditing\AuditLogger;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly FinalizePurchaseAction $finalizePurchaseAction,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = Invoice::where('type', 'purchase')->with(['party', 'warehouse', 'creator'])->latest();

        if ($search = $request->input('search')) {
            $asciiSearch = LocalizedDigits::toAscii($search);
            $query->where(function ($q) use ($search, $asciiSearch) {
                $q->where('invoice_number', 'like', "%{$asciiSearch}%")
                    ->orWhereHas('party', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $purchases = $query->paginate(15)->withQueryString();

        return view('purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        Gate::authorize('create', Invoice::class);

        $suppliers = Party::whereHas('roles', fn ($q) => $q->where('role', 'supplier'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $variants = ProductVariant::with('product')->get();
        $pendingDevices = Device::where('operational_status', 'pending_receipt')->with('variant.product')->get();

        return view('purchases.create', compact('suppliers', 'warehouses', 'variants', 'pendingDevices'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'party_id' => ['required', 'exists:parties,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'issue_date' => ['required', 'date'],
            'additional_cost_toman' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'lines.*.device_id' => ['nullable', 'exists:devices,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price_toman' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', 'string', 'in:amount,percentage'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:amount,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'payment_type' => ['required', 'in:cash,installment'],
            'down_payment_toman' => ['nullable', 'numeric', 'min:0'],
            'installment_notes' => ['nullable', 'string', 'max:1000'],
            'checks' => ['nullable', 'required_if:payment_type,installment', 'array'],
            'checks.*.check_number' => ['required_if:payment_type,installment', 'string', 'max:100'],
            'checks.*.sayad_id' => ['nullable', 'digits:16'],
            'checks.*.bank_name' => ['required_if:payment_type,installment', 'string', 'max:100'],
            'checks.*.account_owner' => ['nullable', 'string', 'max:150'],
            'checks.*.amount_toman' => ['required_if:payment_type,installment', 'numeric', 'min:1'],
            'checks.*.due_date' => ['required_if:payment_type,installment', 'date'],
        ]);

        $additionalCostRials = ! empty($validated['additional_cost_toman'])
            ? (int) ($validated['additional_cost_toman'] * 10)
            : 0;

        $invoice = DB::transaction(function () use ($validated, $additionalCostRials, $request) {
            $countToday = Invoice::whereDate('created_at', now()->toDateString())->count();
            $invoiceNumber = 'PUR-' . now()->format('Ymd') . '-' . str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'type' => 'purchase',
                'invoice_number' => $invoiceNumber,
                'party_id' => $validated['party_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'issue_date' => $validated['issue_date'],
                'status' => 'draft',
                'additional_cost_rials' => $additionalCostRials,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $subtotalRials = 0;
            $lineDiscountRialsTotal = 0;
            $createdLines = [];

            foreach ($validated['lines'] as $lineIndex => $lineData) {
                $variant = ProductVariant::with('product')->findOrFail($lineData['product_variant_id']);
                $qty = (int) $lineData['quantity'];
                $unitPriceRials = (int) ($lineData['unit_price_toman'] * 10);
                $lineSubtotal = $qty * $unitPriceRials;
                $discountType = $lineData['discount_type'] ?? 'amount';
                $discountValue = (float) ($lineData['discount_value'] ?? 0);
                if ($discountType === 'percentage' && $discountValue > 100) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "lines.{$lineIndex}.discount_value" => 'درصد تخفیف ردیف نمی‌تواند بیشتر از ۱۰۰ باشد.',
                    ]);
                }
                $lineDiscountRials = $discountType === 'percentage'
                    ? (int) floor($lineSubtotal * $discountValue / 100)
                    : (int) ($discountValue * 10);
                if ($lineDiscountRials > $lineSubtotal) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "lines.{$lineIndex}.discount_value" => 'تخفیف ردیف نمی‌تواند از مبلغ همان ردیف بیشتر باشد.',
                    ]);
                }
                $subtotalRials += $lineSubtotal;
                $lineDiscountRialsTotal += $lineDiscountRials;

                $createdLines[] = InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'device_id' => $lineData['device_id'] ?? null,
                    'quantity' => $qty,
                    'unit_price_rials' => $unitPriceRials,
                    'discount_rials' => $lineDiscountRials,
                ]);
            }

            $discountableRials = $subtotalRials - $lineDiscountRialsTotal;
            $invoiceDiscountType = $validated['discount_type'] ?? 'amount';
            $invoiceDiscountValue = (float) ($validated['discount_value'] ?? 0);
            if ($invoiceDiscountType === 'percentage' && $invoiceDiscountValue > 100) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'discount_value' => 'درصد تخفیف کل نمی‌تواند بیشتر از ۱۰۰ باشد.',
                ]);
            }
            $invoiceLevelDiscountRials = $invoiceDiscountType === 'percentage'
                ? (int) floor($discountableRials * $invoiceDiscountValue / 100)
                : (int) ($invoiceDiscountValue * 10);
            if ($invoiceLevelDiscountRials > $discountableRials) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'discount_value' => 'تخفیف کل نمی‌تواند از مبلغ باقی‌مانده فاکتور بیشتر باشد.',
                ]);
            }

            $allocated = 0;
            foreach ($createdLines as $index => $createdLine) {
                $lineNet = ($createdLine->quantity * $createdLine->unit_price_rials) - $createdLine->discount_rials;
                $share = $index === count($createdLines) - 1
                    ? $invoiceLevelDiscountRials - $allocated
                    : ($discountableRials > 0 ? intdiv($lineNet * $invoiceLevelDiscountRials, $discountableRials) : 0);
                $createdLine->increment('discount_rials', $share);
                $allocated += $share;
            }

            $discountRials = $lineDiscountRialsTotal + $invoiceLevelDiscountRials;

            $invoice->subtotal_rials = $subtotalRials;
            $invoice->discount_rials = $discountRials;
            $invoice->total_amount_rials = ($subtotalRials - $discountRials) + $additionalCostRials;
            $invoice->save();

            $plan = PurchasePaymentPlan::create([
                'invoice_id' => $invoice->id,
                'payment_type' => $validated['payment_type'],
                'down_payment_rials' => (int) (($validated['down_payment_toman'] ?? 0) * 10),
                'installments_count' => $validated['payment_type'] === 'installment' ? count($validated['checks'] ?? []) : 0,
                'notes' => $validated['installment_notes'] ?? null,
            ]);

            if ($validated['payment_type'] === 'installment') {
                foreach ($validated['checks'] ?? [] as $check) {
                    $plan->checks()->create([
                        'check_number' => $check['check_number'], 'sayad_id' => $check['sayad_id'] ?? null,
                        'bank_name' => $check['bank_name'], 'account_owner' => $check['account_owner'] ?? null,
                        'amount_rials' => (int) ($check['amount_toman'] * 10), 'due_date' => $check['due_date'],
                    ]);
                    Cheque::create([
                        'direction' => 'issued', 'status' => 'scheduled', 'party_id' => $invoice->party_id,
                        'source_invoice_id' => $invoice->id, 'check_number' => $check['check_number'],
                        'sayad_id' => $check['sayad_id'] ?? null, 'bank_name' => $check['bank_name'],
                        'account_owner' => $check['account_owner'] ?? null,
                        'amount_rials' => (int) ($check['amount_toman'] * 10), 'due_date' => $check['due_date'],
                    ]);
                }
            }

            return $invoice;
        });

        $this->auditLogger->record(
            'purchase.created',
            $invoice,
            [],
            ['invoice_number' => $invoice->invoice_number, 'status' => 'draft'],
            $request->user(),
        );

        return redirect()->route('purchases.show', $invoice)
            ->with('status', 'پیش‌نویس فاکتور خرید با موفقیت ذخیره شد.');
    }

    public function show(Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['party', 'warehouse', 'lines.product', 'lines.variant', 'lines.device.identifiers', 'allocations.payment', 'purchasePaymentPlan.checks']);

        return view('purchases.show', compact('invoice'));
    }

    public function finalize(Invoice $invoice, Request $request): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $this->finalizePurchaseAction->execute($invoice, $request->user());

        $this->auditLogger->record(
            'purchase.finalized',
            $invoice,
            ['status' => 'draft'],
            ['status' => 'finalized'],
            $request->user(),
        );

        return redirect()->route('purchases.show', $invoice)
            ->with('status', 'فاکتور خرید با موفقیت نهایی شد، موجودی انبار به‌روزرسانی گردید و سند دوبل صادر شد.');
    }
}

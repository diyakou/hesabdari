<?php

namespace App\Http\Controllers;

use App\Actions\Invoicing\FinalizeSaleAction;
use App\Actions\Parties\UpsertPartyAction;
use App\Enums\DeviceStatus;
use App\Enums\PartyType;
use App\Enums\ProductType;
use App\Models\Device;
use App\Models\FinancialAccount;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Party;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\Auditing\AuditLogger;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly FinalizeSaleAction $finalizeSaleAction,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Invoice::class);

        $query = Invoice::where('type', 'sale')->with(['party', 'warehouse', 'creator'])->latest();

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

        $sales = $query->paginate(15)->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create(): View
    {
        Gate::authorize('create', Invoice::class);

        $customers = Party::whereHas('roles', fn ($q) => $q->where('role', 'customer'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $variants = ProductVariant::with('product')->get();
        $availableDevices = Device::where('operational_status', DeviceStatus::Available)
            ->with(['variant.product', 'identifiers'])
            ->get();
        $financialAccounts = FinancialAccount::where('is_active', true)->get();

        return view('sales.create', compact('customers', 'warehouses', 'variants', 'availableDevices', 'financialAccounts'));
    }

    public function quickParty(Request $request, UpsertPartyAction $action): JsonResponse
    {
        Gate::authorize('create', Party::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:individual,company'],
            'mobile' => ['nullable', 'string', 'max:30'],
        ]);

        $party = $action->execute([
            'name' => $validated['name'],
            'type' => PartyType::from($validated['type']),
            'roles' => ['customer'],
            'mobile' => $validated['mobile'] ?? null,
            'is_active' => true,
        ]);

        $this->auditLogger->record('party.created', $party, [], ['name' => $party->name, 'roles' => ['customer']], $request->user());

        return response()->json([
            'id' => $party->id,
            'label' => $party->name.($party->mobile ? " ({$party->mobile})" : ''),
        ], 201);
    }

    public function quickProduct(Request $request): JsonResponse
    {
        Gate::authorize('create', Product::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:stock,service'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:product_variants,barcode'],
            'selling_price_toman' => ['required', 'numeric', 'min:0'],
        ]);

        $variant = DB::transaction(function () use ($validated): ProductVariant {
            $product = Product::create([
                'name' => trim($validated['name']),
                'type' => ProductType::from($validated['type']),
                'is_active' => true,
            ]);

            return ProductVariant::create([
                'product_id' => $product->id,
                'sku' => sprintf('PRD-%06d', $product->id),
                'barcode' => ! empty($validated['barcode']) ? trim((string) LocalizedDigits::toAscii($validated['barcode'])) : null,
                'selling_price_rials' => (int) ($validated['selling_price_toman'] * 10),
                'min_selling_price_rials' => (int) ($validated['selling_price_toman'] * 10),
            ])->load('product');
        });

        $this->auditLogger->record('product.created', $variant->product, [], ['name' => $variant->product->name, 'sku' => $variant->sku], $request->user());

        return response()->json([
            'id' => $variant->id,
            'label' => $variant->display_name.' ('.$variant->product->type->label().')',
            'selling_price_toman' => (int) ($variant->selling_price_rials / 10),
        ], 201);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'party_id' => ['required', 'exists:parties,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'issue_date' => ['required', 'date'],
            'discount_toman' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'lines.*.device_id' => ['nullable', 'exists:devices,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price_toman' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_type' => ['nullable', 'string', 'in:amount,percentage'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'finalize_now' => ['nullable', 'boolean'],
            'payment' => ['nullable', 'array'],
            'payment.amount_toman' => ['nullable', 'numeric', 'min:0'],
            'payment.financial_account_id' => ['nullable', 'exists:financial_accounts,id'],
            'payment.payment_method' => ['nullable', 'string', 'in:cash,pos,bank_transfer'],
        ]);

        $invoiceDiscountRials = ! empty($validated['discount_toman'])
            ? (int) ($validated['discount_toman'] * 10)
            : 0;

        $invoice = DB::transaction(function () use ($validated, $invoiceDiscountRials, $request) {
            $countToday = Invoice::whereDate('created_at', now()->toDateString())->count();
            $invoiceNumber = 'SAL-' . now()->format('Ymd') . '-' . str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'type' => 'sale',
                'invoice_number' => $invoiceNumber,
                'party_id' => $validated['party_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'issue_date' => $validated['issue_date'],
                'status' => 'draft',
                'discount_rials' => $invoiceDiscountRials,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $subtotalRials = 0;
            $lineDiscountRialsTotal = 0;

            foreach ($validated['lines'] as $lineIndex => $lineData) {
                $variant = ProductVariant::with('product')->findOrFail($lineData['product_variant_id']);
                $qty = (int) $lineData['quantity'];
                $unitPriceRials = (int) ($lineData['unit_price_toman'] * 10);

                $lineSubtotal = $qty * $unitPriceRials;
                $discountType = $lineData['discount_type'] ?? 'amount';
                $discountValue = $lineData['discount_value'] ?? 0;

                if ($discountType === 'percentage') {
                    if ($discountValue > 100) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "lines.{$lineIndex}.discount_value" => 'درصد تخفیف هر ردیف نمی‌تواند بیشتر از ۱۰۰ باشد.',
                        ]);
                    }

                    $lineDiscountRials = (int) floor($lineSubtotal * $discountValue / 100);
                } else {
                    $lineDiscountRials = (int) ($discountValue * 10);
                }

                if ($lineDiscountRials > $lineSubtotal) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "lines.{$lineIndex}.discount_value" => 'تخفیف ردیف نمی‌تواند از مبلغ همان ردیف بیشتر باشد.',
                    ]);
                }

                $subtotalRials += $lineSubtotal;
                $lineDiscountRialsTotal += $lineDiscountRials;

                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'device_id' => $lineData['device_id'] ?? null,
                    'quantity' => $qty,
                    'unit_price_rials' => $unitPriceRials,
                    'discount_rials' => $lineDiscountRials,
                ]);
            }

            if ($invoiceDiscountRials > ($subtotalRials - $lineDiscountRialsTotal)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'discount_toman' => 'تخفیف کل فاکتور نمی‌تواند از مبلغ باقی‌مانده پس از تخفیف ردیف‌ها بیشتر باشد.',
                ]);
            }

            $invoice->subtotal_rials = $subtotalRials;
            $invoice->total_amount_rials = $subtotalRials - $lineDiscountRialsTotal - $invoiceDiscountRials;
            $invoice->save();

            return $invoice;
        });

        // If requested to finalize immediately (standard POS checkout)
        if ($request->boolean('finalize_now', true)) {
            $paymentList = [];
            if (! empty($validated['payment']['amount_toman']) && ! empty($validated['payment']['financial_account_id'])) {
                $paymentList[] = [
                    'financial_account_id' => (int) $validated['payment']['financial_account_id'],
                    'amount_rials' => (int) ($validated['payment']['amount_toman'] * 10),
                    'payment_method' => $validated['payment']['payment_method'] ?? 'cash',
                ];
            }

            $this->finalizeSaleAction->execute($invoice, $paymentList, $request->user());

            $this->auditLogger->record(
                'sale.finalized',
                $invoice,
                ['status' => 'draft'],
                ['status' => 'finalized', 'invoice_number' => $invoice->invoice_number],
                $request->user(),
            );

            return redirect()->route('sales.show', $invoice)
                ->with('status', 'فاکتور فروش با موفقیت نهایی شد و کالاها از انبار کسر گردیدند.');
        }

        return redirect()->route('sales.show', $invoice)
            ->with('status', 'پیش‌نویس فاکتور فروش ذخیره شد.');
    }

    public function show(Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['party', 'warehouse', 'lines.product', 'lines.variant', 'lines.device.identifiers', 'allocations.payment']);

        return view('sales.show', compact('invoice'));
    }

    public function print(Invoice $invoice, Request $request): View
    {
        Gate::authorize('view', $invoice);

        $format = $request->input('format', 'a4'); // 'a4' or 'thermal'
        $invoice->load(['party', 'warehouse', 'lines.product', 'lines.variant', 'lines.device.identifiers', 'allocations.payment']);

        return view('sales.print', compact('invoice', 'format'));
    }
}

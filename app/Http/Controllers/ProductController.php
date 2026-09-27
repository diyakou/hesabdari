<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly \App\Services\Auditing\AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $query = Product::with(['brand', 'category', 'variants'])->latest();

        if ($search = $request->input('search')) {
            $asciiSearch = LocalizedDigits::toAscii($search);
            $query->where(function ($q) use ($search, $asciiSearch) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($vq) use ($search, $asciiSearch) {
                        $vq->where('sku', 'like', "%{$asciiSearch}%")
                            ->orWhere('barcode', 'like', "%{$asciiSearch}%");
                    });
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->paginate(15)->withQueryString();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'brands', 'categories'));
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('products.create', compact('brands', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:stock,serialized,service'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:product_variants,barcode'],
            'color' => ['nullable', 'string', 'max:100'],
            'storage' => ['nullable', 'string', 'max:100'],
            'ram' => ['nullable', 'string', 'max:100'],
            'selling_price_toman' => ['required', 'numeric', 'min:0'],
            'min_selling_price_toman' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sellingPriceRials = (int) ($validated['selling_price_toman'] * 10);
        $minSellingPriceRials = ! empty($validated['min_selling_price_toman'])
            ? (int) ($validated['min_selling_price_toman'] * 10)
            : $sellingPriceRials;

        $product = DB::transaction(function () use ($validated, $sellingPriceRials, $minSellingPriceRials, $request) {
            $product = Product::create([
                'name' => trim($validated['name']),
                'type' => ProductType::from($validated['type']),
                'brand_id' => $validated['brand_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => sprintf('PRD-%06d', $product->id),
                'barcode' => ! empty($validated['barcode']) ? trim((string) LocalizedDigits::toAscii($validated['barcode'])) : null,
                'color' => $validated['color'] ?? null,
                'storage' => $validated['storage'] ?? null,
                'ram' => $validated['ram'] ?? null,
                'selling_price_rials' => $sellingPriceRials,
                'min_selling_price_rials' => $minSellingPriceRials,
            ]);

            return $product;
        });

        $this->auditLogger->record(
            'product.created',
            $product,
            [],
            ['name' => $product->name, 'sku' => $product->variants()->value('sku')],
            $request->user(),
        );

        return redirect()->route('products.index')
            ->with('status', 'کالا/خدمت جدید با موفقیت ایجاد شد.');
    }
}

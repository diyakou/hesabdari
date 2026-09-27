<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Brand::class);

        $query = Brand::withCount('products')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $brands = $query->paginate(15)->withQueryString();

        return view('brands.index', compact('brands'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Brand::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($validated['name']);
        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'], '-', null)
            : Str::slug($name, '-', null);

        if (empty($slug)) {
            $slug = 'brand-' . Str::random(6);
        }

        // Ensure slug uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (Brand::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $brand = Brand::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->record(
            'brand.created',
            $brand,
            [],
            ['name' => $brand->name, 'slug' => $brand->slug],
            $request->user(),
        );

        return redirect()->route('brands.index')
            ->with('status', 'برند جدید با موفقیت ایجاد شد.');
    }

    public function edit(Brand $brand): View
    {
        Gate::authorize('update', $brand);

        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($validated['name']);
        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'], '-', null)
            : Str::slug($name, '-', null);

        if (empty($slug)) {
            $slug = 'brand-' . Str::random(6);
        }

        $baseSlug = $slug;
        $counter = 1;
        while (Brand::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $oldAttributes = $brand->only(['name', 'slug', 'is_active']);

        $brand->update([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditLogger->record(
            'brand.updated',
            $brand,
            $oldAttributes,
            $brand->only(['name', 'slug', 'is_active']),
            $request->user(),
        );

        return redirect()->route('brands.index')
            ->with('status', 'اطلاعات برند با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse
    {
        Gate::authorize('delete', $brand);

        if ($brand->products()->exists()) {
            return back()->withErrors([
                'brand_delete' => 'امکان حذف این برند وجود ندارد زیرا کالاهایی به آن متصل هستند. برای عدم نمایش، می‌توانید وضعیت آن را غیرفعال کنید.',
            ]);
        }

        $brandName = $brand->name;
        $brand->delete();

        $this->auditLogger->record(
            'brand.deleted',
            $brand,
            ['name' => $brandName],
            [],
            $request->user(),
        );

        return redirect()->route('brands.index')
            ->with('status', "برند «{$brandName}» با موفقیت حذف شد.");
    }
}

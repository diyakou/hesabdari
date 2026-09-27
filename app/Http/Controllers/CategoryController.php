<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Category::class);

        $query = Category::withCount('products')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $categories = $query->paginate(15)->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

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
            $slug = 'cat-' . Str::random(6);
        }

        $baseSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $category = Category::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->record(
            'category.created',
            $category,
            [],
            ['name' => $category->name, 'slug' => $category->slug],
            $request->user(),
        );

        return redirect()->route('categories.index')
            ->with('status', 'دسته‌بندی جدید با موفقیت ایجاد شد.');
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($validated['name']);
        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'], '-', null)
            : Str::slug($name, '-', null);

        if (empty($slug)) {
            $slug = 'cat-' . Str::random(6);
        }

        $baseSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $oldAttributes = $category->only(['name', 'slug', 'is_active']);

        $category->update([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditLogger->record(
            'category.updated',
            $category,
            $oldAttributes,
            $category->only(['name', 'slug', 'is_active']),
            $request->user(),
        );

        return redirect()->route('categories.index')
            ->with('status', 'اطلاعات دسته‌بندی با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->products()->exists()) {
            return back()->withErrors([
                'category_delete' => 'امکان حذف این دسته‌بندی وجود ندارد زیرا کالاهایی به آن متصل هستند. برای عدم نمایش، می‌توانید وضعیت آن را غیرفعال کنید.',
            ]);
        }

        $categoryName = $category->name;
        $category->delete();

        $this->auditLogger->record(
            'category.deleted',
            $category,
            ['name' => $categoryName],
            [],
            $request->user(),
        );

        return redirect()->route('categories.index')
            ->with('status', "دسته‌بندی «{$categoryName}» با موفقیت حذف شد.");
    }
}

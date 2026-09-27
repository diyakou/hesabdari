@extends('layouts.app')

@section('title', 'کالاها و خدمات')
@section('page-heading', 'کالاها و خدمات')
@section('page-description', 'مدیریت محصولات، اقلام سریالی، تنوع‌ها و خدمات فروشگاه')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="جستجو با عنوان، SKU یا بارکد..."
                class="input-text max-w-xs"
            >
            <select name="type" class="input-text max-w-xs">
                <option value="">همه انواع</option>
                <option value="stock" @selected(request('type') === 'stock')>کالای تعدادی</option>
                <option value="serialized" @selected(request('type') === 'serialized')>دستگاه سریالی / گوشی</option>
                <option value="service" @selected(request('type') === 'service')>خدمات</option>
            </select>
            <select name="brand_id" class="input-text max-w-xs">
                <option value="">همه برندها</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->hasAny(['search', 'type', 'brand_id']))
                <a href="{{ route('products.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            @can('viewAny', App\Models\Brand::class)
                <a href="{{ route('brands.index') }}" class="button-secondary">
                    مدیریت برندها
                </a>
            @endcan
            @can('viewAny', App\Models\Category::class)
                <a href="{{ route('categories.index') }}" class="button-secondary">
                    مدیریت دسته‌بندی‌ها
                </a>
            @endcan
            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}" class="button-primary">
                    + تعریف محصول / خدمت جدید
                </a>
            @endcan
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">عنوان محصول / خدمت</th>
                    <th class="p-4">نوع</th>
                    <th class="p-4">برند و دسته‌بندی</th>
                    <th class="p-4">SKU پیش‌فرض</th>
                    <th class="p-4">قیمت فروش (تومان)</th>
                    <th class="p-4">حداقل قیمت (تومان)</th>
                    <th class="p-4">وضعیت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    @php $firstVariant = $product->variants->first(); @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-semibold text-slate-900">
                            {{ $product->name }}
                        </td>
                        <td class="p-4">
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                {{ $product->type->label() }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $product->brand?->name ?: '—' }} / {{ $product->category?->name ?: '—' }}
                        </td>
                        <td class="p-4 font-mono text-xs" dir="ltr">
                            {{ $firstVariant?->sku ?: '—' }}
                        </td>
                        <td class="p-4 font-semibold text-teal-700">
                            {{ $firstVariant ? number_format($firstVariant->selling_price_rials / 10) : '—' }}
                        </td>
                        <td class="p-4 text-xs text-slate-500">
                            {{ $firstVariant ? number_format($firstVariant->min_selling_price_rials / 10) : '—' }}
                        </td>
                        <td class="p-4">
                            @if($product->is_active)
                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">فعال</span>
                            @else
                                <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">غیرفعال</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-500">هیچ محصول یا خدمتی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $products->links() }}
    </div>
</div>
@endsection

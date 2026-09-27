@extends('layouts.app')

@section('title', 'تعریف محصول یا خدمت جدید')
@section('page-heading', 'تعریف محصول / خدمت جدید')

@section('content')
<div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('products.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="label">عنوان کالا یا خدمت <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="مثلاً: iPhone 13 128GB یا تعویض گلس" required class="input-text" autofocus>
            </div>

            <div>
                <label for="type" class="label">نوع <span class="text-rose-500">*</span></label>
                <select id="type" name="type" required class="input-text">
                    <option value="serialized" @selected(old('type') === 'serialized')>دستگاه سریالی / گوشی (دارای IMEI)</option>
                    <option value="stock" @selected(old('type') === 'stock')>کالای تعدادی (لوازم جانبی، کابل و...)</option>
                    <option value="service" @selected(old('type') === 'service')>خدمات (نرم‌افزاری، راه‌اندازی و...)</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="brand_id" class="label mb-0">برند</label>
                    @can('create', App\Models\Brand::class)
                        <a href="{{ route('brands.index') }}" target="_blank" class="text-xs text-teal-600 hover:text-teal-700 font-medium">+ مدیریت برندها</a>
                    @endcan
                </div>
                <select id="brand_id" name="brand_id" class="input-text">
                    <option value="">بدون برند / متفرقه</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="category_id" class="label mb-0">دسته‌بندی</label>
                    @can('create', App\Models\Category::class)
                        <a href="{{ route('categories.index') }}" target="_blank" class="text-xs text-teal-600 hover:text-teal-700 font-medium">+ مدیریت دسته‌بندی‌ها</a>
                    @endcan
                </div>
                <select id="category_id" name="category_id" class="input-text">
                    <option value="">بدون دسته‌بندی</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4">
            <h3 class="text-sm font-bold text-slate-800 mb-3">مشخصات تنوع پیش‌فرض (کد کالا و قیمت‌ها)</h3>
            <p class="mb-3 text-xs text-slate-500">کد کالا به‌صورت خودکار پس از ثبت کالا تولید می‌شود.</p>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="barcode" class="label">بارکد</label>
                    <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}" dir="ltr" class="input-text font-mono">
                </div>

                <div>
                    <label for="color" class="label">رنگ</label>
                    <input type="text" id="color" name="color" value="{{ old('color') }}" placeholder="آبی، مشکی و..." class="input-text">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="storage" class="label">حافظه داخلی</label>
                    <select id="storage" name="storage" dir="ltr" class="input-text font-mono">
                        <option value="">انتخاب ظرفیت...</option>
                        @foreach(['16GB', '32GB', '64GB', '128GB', '256GB', '512GB', '1TB', '2TB'] as $capacity)
                            <option value="{{ $capacity }}" @selected(old('storage') === $capacity)>{{ $capacity }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="ram" class="label">حافظه رم (RAM)</label>
                    <input type="text" id="ram" name="ram" value="{{ old('ram') }}" placeholder="4GB" dir="ltr" class="input-text font-mono">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="selling_price_toman" class="label">قیمت فروش پایه (تومان) <span class="text-rose-500">*</span></label>
                    <input type="number" id="selling_price_toman" name="selling_price_toman" value="{{ old('selling_price_toman') }}" min="0" required class="input-text font-mono">
                </div>

                <div>
                    <label for="min_selling_price_toman" class="label">حداقل قیمت مجاز فروش (تومان)</label>
                    <input type="number" id="min_selling_price_toman" name="min_selling_price_toman" value="{{ old('min_selling_price_toman') }}" min="0" placeholder="در صورت تخفیف حداکثری" class="input-text font-mono">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('products.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">ذخیره کالا / خدمت</button>
        </div>
    </form>
</div>
@endsection

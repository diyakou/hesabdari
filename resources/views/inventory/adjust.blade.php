@extends('layouts.app')

@section('title', 'ثبت تعدیل انبارگردانی')
@section('page-heading', 'ثبت تعدیل انبارگردانی (کسری / اضافات)')
@section('page-description', 'اصلاح موجودی فیزیکی انبار با مستندسازی دلیل و صدور خودکار سند درآمد/هزینه تعدیل انبار')

@section('content')
<div class="mx-auto max-w-2xl">
    <form method="POST" action="{{ route('inventory.adjust.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">مشخصات صورت‌جلسه تعدیل</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        کالا / تنوع محصول <span class="text-rose-500">*</span>
                    </label>
                    <select name="product_variant_id" class="input-text w-full" required>
                        <option value="">-- انتخاب کالا --</option>
                        @foreach($variants as $variant)
                            <option value="{{ $variant->id }}" @selected(old('product_variant_id') == $variant->id)>
                                {{ $variant->display_name }} (بارکد: {{ $variant->barcode ?: 'ندارد' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        انبار <span class="text-rose-500">*</span>
                    </label>
                    <select name="warehouse_id" class="input-text w-full" required>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            میزان تغییر تعداد <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="quantity_change"
                            value="{{ old('quantity_change') }}"
                            required
                            placeholder="مثال: ۵+ یا ۳-"
                            class="input-text w-full font-mono text-center"
                            dir="ltr"
                        >
                        <span class="text-xs text-slate-500 mt-1 block">عدد مثبت برای اضافات انبار، عدد منفی برای کسری انبار</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            بهای واحد در صورت اضافات (تومان)
                        </label>
                        <input
                            type="number"
                            name="unit_cost_toman"
                            value="{{ old('unit_cost_toman') }}"
                            placeholder="اختیاری (پیش‌فرض: میانگین فعلی)"
                            class="input-text w-full font-mono"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        علت تعدیل / شماره صورت‌جلسه انبارگردانی <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="reason"
                        rows="3"
                        required
                        placeholder="مثال: مغایرت شمارش پایان فصل، شکستگی در انبار، خطای ثبت ورودی قبلی و..."
                        class="input-text w-full"
                    >{{ old('reason') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('inventory.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">تأیید مدیر و اعمال تعدیل انبار</button>
        </div>
    </form>
</div>
@endsection

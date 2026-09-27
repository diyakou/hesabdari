@extends('layouts.app')

@section('title', 'ثبت دستگاه جدید با شناسه IMEI')
@section('page-heading', 'ثبت دستگاه فیزیکی (IMEI)')
@section('page-description', 'ورود اطلاعات گوشی، IMEI اول و دوم، درصد سلامت باتری و رجیستری')

@section('content')
<div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('devices.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="product_variant_id" class="label">مدل و تنوع گوشی <span class="text-rose-500">*</span></label>
                <select id="product_variant_id" name="product_variant_id" required class="input-text">
                    <option value="">انتخاب مدل...</option>
                    @foreach($variants as $variant)
                        <option value="{{ $variant->id }}" @selected(old('product_variant_id') == $variant->id)>
                            {{ $variant->display_name }} (SKU: {{ $variant->sku }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="warehouse_id" class="label">انبار محل استقرار</label>
                <select id="warehouse_id" name="warehouse_id" class="input-text">
                    <option value="">بدون انبار / نامشخص</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4">
            <h3 class="text-sm font-bold text-slate-800 mb-3">شناسه‌های یکتا (IMEI دقیقاً ۱۵ رقم عددی)</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="primary_imei" class="label">شناسه IMEI اول (اصلی) <span class="text-rose-500">*</span></label>
                    <input type="text" id="primary_imei" name="primary_imei" value="{{ old('primary_imei') }}" maxlength="15" required placeholder="352094101234567" dir="ltr" class="input-text font-mono tracking-wider">
                    <p class="text-xs text-slate-400 mt-1">۱۵ رقم عددی، ارقام فارسی خودکار تبدیل می‌شوند.</p>
                </div>

                <div>
                    <label for="secondary_imei" class="label">شناسه IMEI دوم (اختیاری برای گوشی دو سیم‌کارت)</label>
                    <input type="text" id="secondary_imei" name="secondary_imei" value="{{ old('secondary_imei') }}" maxlength="15" placeholder="352094101234568" dir="ltr" class="input-text font-mono tracking-wider">
                </div>
            </div>

            <div class="mt-4">
                <label for="serial_number" class="label">شماره سریال دستگاه (اختیاری)</label>
                <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number') }}" dir="ltr" class="input-text font-mono">
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4">
            <h3 class="text-sm font-bold text-slate-800 mb-3">وضعیت کارشناسی و سلامت فیزیکی</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="physical_condition" class="label">وضعیت فیزیکی <span class="text-rose-500">*</span></label>
                    <select id="physical_condition" name="physical_condition" required class="input-text">
                        <option value="new" @selected(old('physical_condition') === 'new')>نو (آکبند)</option>
                        <option value="used" @selected(old('physical_condition') === 'used')>کارکرده (دست دوم)</option>
                    </select>
                </div>

                <div class="battery-control" data-battery-control>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="battery_health" class="label mb-0">سلامت باتری</label>
                        <output for="battery_health" class="battery-value"><b data-battery-value>{{ old('battery_health', 100) }}</b><span>٪</span></output>
                    </div>
                    <input type="range" id="battery_health" name="battery_health" value="{{ old('battery_health', 100) }}" min="0" max="100" step="1" dir="ltr" class="battery-slider" aria-label="درصد سلامت باتری">
                    <div class="mt-1 flex justify-between text-[10px] text-slate-400" dir="ltr"><span>0%</span><span>100%</span></div>
                </div>

                <div>
                    <label for="registry_status" class="label">وضعیت رجیستری <span class="text-rose-500">*</span></label>
                    <select id="registry_status" name="registry_status" required class="input-text">
                        <option value="registered" @selected(old('registry_status') === 'registered')>رجیستر شده</option>
                        <option value="unknown" @selected(old('registry_status') === 'unknown')>نامشخص</option>
                        <option value="unregistered" @selected(old('registry_status') === 'unregistered')>رجیستر نشده / مسافری</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="ownership" class="label">مالکیت دستگاه <span class="text-rose-500">*</span></label>
                    <select id="ownership" name="ownership" required class="input-text">
                        <option value="shop" @selected(old('ownership') === 'shop')>متعلق به فروشگاه (موجودی انبار)</option>
                        <option value="customer" @selected(old('ownership') === 'customer')>متعلق به مشتری (دستگاه خدماتی / تعمیری)</option>
                    </select>
                </div>

                <div>
                    <label for="operational_status" class="label">وضعیت عملیاتی دستگاه <span class="text-rose-500">*</span></label>
                    <select id="operational_status" name="operational_status" required class="input-text">
                        <option value="available" @selected(old('operational_status') === 'available')>موجود در انبار برای فروش</option>
                        <option value="pending_receipt" @selected(old('operational_status') === 'pending_receipt')>در انتظار ورود</option>
                        <option value="quarantine" @selected(old('operational_status') === 'quarantine')>قرنطینه / بررسی</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <label for="notes" class="label">توضیحات و یادداشت کارشناسی</label>
            <textarea id="notes" name="notes" rows="2" class="input-text">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('devices.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">ثبت دستگاه</button>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('title', 'تنظیمات فروشگاه')
@section('page-heading', 'تنظیمات فروشگاه')
@section('page-description', 'مشخصات فروشگاه، شعبه اصلی و انبار اصلی')

@section('content')
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6" novalidate>
        @csrf
        @method('PUT')

        <section class="card p-5 sm:p-7" aria-labelledby="store-section-title">
            <div class="mb-6 border-b border-slate-200 pb-5">
                <h2 id="store-section-title" class="text-lg font-black text-slate-950">مشخصات فروشگاه</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">اطلاعات عمومی که در اسناد و بخش‌های داخلی سامانه استفاده می‌شود.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="store_name" class="form-label">نام فروشگاه</label>
                    <input id="store_name" name="store_name" type="text" value="{{ old('store_name', $store?->name) }}" class="form-input" maxlength="120" autocomplete="organization" required autofocus @error('store_name') aria-invalid="true" aria-describedby="store_name-error" @enderror>
                    <x-field-error name="store_name" />
                </div>

                <div>
                    <label for="legal_name" class="form-label">نام حقوقی <span class="font-normal text-slate-400">(اختیاری)</span></label>
                    <input id="legal_name" name="legal_name" type="text" value="{{ old('legal_name', $store?->legal_name) }}" class="form-input" maxlength="160" @error('legal_name') aria-invalid="true" aria-describedby="legal_name-error" @enderror>
                    <x-field-error name="legal_name" />
                </div>

                <div>
                    <label for="store_phone" class="form-label">تلفن فروشگاه <span class="font-normal text-slate-400">(اختیاری)</span></label>
                    <input id="store_phone" name="store_phone" type="tel" value="{{ old('store_phone', $store?->phone) }}" class="form-input text-left" dir="ltr" maxlength="16" autocomplete="tel" inputmode="tel" placeholder="02112345678" @error('store_phone') aria-invalid="true" aria-describedby="store_phone-error" @enderror>
                    <x-field-error name="store_phone" />
                </div>

                <div class="sm:col-span-2">
                    <label for="store_address" class="form-label">نشانی فروشگاه <span class="font-normal text-slate-400">(اختیاری)</span></label>
                    <textarea id="store_address" name="store_address" rows="3" class="form-input resize-y" maxlength="1000" autocomplete="street-address" @error('store_address') aria-invalid="true" aria-describedby="store_address-error" @enderror>{{ old('store_address', $store?->address) }}</textarea>
                    <x-field-error name="store_address" />
                </div>
            </div>
        </section>

        <section class="card p-5 sm:p-7" aria-labelledby="branch-section-title">
            <div class="mb-6 border-b border-slate-200 pb-5">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 id="branch-section-title" class="text-lg font-black text-slate-950">شعبه اصلی</h2>
                    <span class="badge bg-cyan-100 text-cyan-800" dir="ltr">MAIN</span>
                </div>
                <p class="mt-1 text-sm leading-6 text-slate-500">در مرحله زیرساخت یک شعبه فعال و پیش‌فرض نگهداری می‌شود.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="branch_name" class="form-label">نام شعبه</label>
                    <input id="branch_name" name="branch_name" type="text" value="{{ old('branch_name', $branch?->name) }}" class="form-input" maxlength="120" required @error('branch_name') aria-invalid="true" aria-describedby="branch_name-error" @enderror>
                    <x-field-error name="branch_name" />
                </div>

                <div>
                    <label for="branch_phone" class="form-label">تلفن شعبه <span class="font-normal text-slate-400">(اختیاری)</span></label>
                    <input id="branch_phone" name="branch_phone" type="tel" value="{{ old('branch_phone', $branch?->phone) }}" class="form-input text-left" dir="ltr" maxlength="16" inputmode="tel" placeholder="02112345678" @error('branch_phone') aria-invalid="true" aria-describedby="branch_phone-error" @enderror>
                    <x-field-error name="branch_phone" />
                </div>

                <div class="sm:col-span-2">
                    <label for="branch_address" class="form-label">نشانی شعبه <span class="font-normal text-slate-400">(اختیاری)</span></label>
                    <textarea id="branch_address" name="branch_address" rows="3" class="form-input resize-y" maxlength="1000" @error('branch_address') aria-invalid="true" aria-describedby="branch_address-error" @enderror>{{ old('branch_address', $branch?->address) }}</textarea>
                    <x-field-error name="branch_address" />
                </div>
            </div>
        </section>

        <section class="card p-5 sm:p-7" aria-labelledby="warehouse-section-title">
            <div class="mb-6 border-b border-slate-200 pb-5">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 id="warehouse-section-title" class="text-lg font-black text-slate-950">انبار اصلی</h2>
                    <span class="badge bg-violet-100 text-violet-800" dir="ltr">MAIN-WH</span>
                </div>
                <p class="mt-1 text-sm leading-6 text-slate-500">این انبار به شعبه اصلی متصل و در مرحله فعلی فعال است.</p>
            </div>

            <div class="max-w-xl">
                <label for="warehouse_name" class="form-label">نام انبار</label>
                <input id="warehouse_name" name="warehouse_name" type="text" value="{{ old('warehouse_name', $warehouse?->name) }}" class="form-input" maxlength="120" required @error('warehouse_name') aria-invalid="true" aria-describedby="warehouse_name-error" @enderror>
                <x-field-error name="warehouse_name" />
            </div>
        </section>

        <div class="sticky bottom-4 flex justify-end rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
            <button type="submit" class="button-primary w-full sm:w-auto">ذخیره تنظیمات</button>
        </div>
    </form>
@endsection

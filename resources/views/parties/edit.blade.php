@extends('layouts.app')

@section('title', 'ویرایش طرف‌حساب')
@section('page-heading', 'ویرایش: ' . $party->name)

@section('content')
<div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('parties.update', $party) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="label">نام شخص یا عنوان شرکت <span class="text-rose-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $party->name) }}" required class="input-text">
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="type" class="label">نوع طرف‌حساب <span class="text-rose-500">*</span></label>
                <select id="type" name="type" required class="input-text">
                    <option value="individual" @selected(old('type', $party->type->value) === 'individual')>شخص حقیقی</option>
                    <option value="company" @selected(old('type', $party->type->value) === 'company')>شخص حقوقی / شرکت</option>
                </select>
            </div>

            <div>
                <label class="label">نقش‌ها در سامانه <span class="text-rose-500">*</span></label>
                @php
                    $currentRoles = old('roles', $party->roles->pluck('role.value')->all());
                @endphp
                <div class="mt-2 flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" name="roles[]" value="customer" @checked(in_array('customer', $currentRoles)) class="rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                        مشتری
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" name="roles[]" value="supplier" @checked(in_array('supplier', $currentRoles)) class="rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                        تأمین‌کننده
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="mobile" class="label">شماره موبایل</label>
                <input type="text" id="mobile" name="mobile" value="{{ old('mobile', $party->mobile) }}" dir="ltr" class="input-text font-mono">
            </div>

            <div>
                <label for="phone" class="label">تلفن ثابت</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $party->phone) }}" dir="ltr" class="input-text font-mono">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="national_id" class="label">کد ملی یا شناسه ملی</label>
                <input type="text" id="national_id" name="national_id" value="{{ old('national_id', $party->national_id) }}" dir="ltr" class="input-text font-mono">
            </div>

            <div>
                <label for="credit_limit_toman" class="label">سقف اعتبار (تومان)</label>
                <input type="number" id="credit_limit_toman" name="credit_limit_toman" value="{{ old('credit_limit_toman', $party->credit_limit_rials ? $party->credit_limit_rials / 10 : '') }}" min="0" class="input-text font-mono">
            </div>
        </div>

        <div>
            <label for="address" class="label">آدرس</label>
            <textarea id="address" name="address" rows="2" class="input-text">{{ old('address', $party->address) }}</textarea>
        </div>

        <div>
            <label for="notes" class="label">یادداشت داخلی</label>
            <textarea id="notes" name="notes" rows="2" class="input-text">{{ old('notes', $party->notes) }}</textarea>
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $party->is_active)) class="rounded border-slate-300 text-teal-600 focus:ring-teal-600">
            <label for="is_active" class="text-sm font-medium text-slate-700">طرف‌حساب فعال است</label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('parties.show', $party) }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">به‌روزرسانی طرف‌حساب</button>
        </div>
    </form>
</div>
@endsection

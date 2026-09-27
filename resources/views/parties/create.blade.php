@extends('layouts.app')

@section('title', 'ثبت طرف‌حساب جدید')
@section('page-heading', 'ثبت طرف‌حساب جدید')
@section('page-description', 'ایجاد مشتری یا تأمین‌کننده با نقش‌های دلخواه')

@section('content')
<div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('parties.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="label">نام شخص یا عنوان شرکت <span class="text-rose-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="input-text" autofocus>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="type" class="label">نوع طرف‌حساب <span class="text-rose-500">*</span></label>
                <select id="type" name="type" required class="input-text">
                    <option value="individual" @selected(old('type') === 'individual')>شخص حقیقی</option>
                    <option value="company" @selected(old('type') === 'company')>شخص حقوقی / شرکت</option>
                </select>
            </div>

            <div>
                <label class="label">نقش‌ها در سامانه <span class="text-rose-500">*</span></label>
                <div class="mt-2 flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" name="roles[]" value="customer" @checked(in_array('customer', old('roles', ['customer']))) class="rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                        مشتری
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" name="roles[]" value="supplier" @checked(in_array('supplier', old('roles', []))) class="rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                        تأمین‌کننده
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="mobile" class="label">شماره موبایل</label>
                <input type="text" id="mobile" name="mobile" value="{{ old('mobile') }}" dir="ltr" placeholder="09123456789" class="input-text font-mono">
            </div>

            <div>
                <label for="phone" class="label">تلفن ثابت</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" class="input-text font-mono">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="national_id" class="label">کد ملی یا شناسه ملی</label>
                <input type="text" id="national_id" name="national_id" value="{{ old('national_id') }}" dir="ltr" class="input-text font-mono">
            </div>

            <div>
                <label for="credit_limit_toman" class="label">سقف اعتبار (تومان)</label>
                <input type="number" id="credit_limit_toman" name="credit_limit_toman" value="{{ old('credit_limit_toman') }}" min="0" placeholder="مثلاً: 50000000" class="input-text font-mono">
            </div>
        </div>

        <div>
            <label for="address" class="label">آدرس</label>
            <textarea id="address" name="address" rows="2" class="input-text">{{ old('address') }}</textarea>
        </div>

        <div>
            <label for="notes" class="label">یادداشت داخلی</label>
            <textarea id="notes" name="notes" rows="2" class="input-text">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('parties.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">ذخیره طرف‌حساب</button>
        </div>
    </form>
</div>
@endsection

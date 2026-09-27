@extends('layouts.app')

@section('title', $type === 'receipt' ? 'ثبت دریافت وجه' : 'ثبت پرداخت وجه')
@section('page-heading', $type === 'receipt' ? 'ثبت سند دریافت وجه از مشتری' : 'ثبت سند پرداخت وجه به تأمین‌کننده')

@section('content')
<div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('payments.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="party_id" class="label">طرف‌حساب <span class="text-rose-500">*</span></label>
                <select id="party_id" name="party_id" required class="input-text">
                    <option value="">انتخاب شخص یا شرکت...</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->id }}" @selected(old('party_id') == $party->id)>{{ $party->name }} ({{ $party->roles->pluck('role.value')->implode(', ') }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="financial_account_id" class="label">حساب مبدأ / مقصد <span class="text-rose-500">*</span></label>
                <select id="financial_account_id" name="financial_account_id" required class="input-text">
                    @foreach($financialAccounts as $acc)
                        <option value="{{ $acc->id }}" @selected(old('financial_account_id') == $acc->id)>{{ $acc->name }} ({{ $acc->type }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="amount_toman" class="label">مبلغ (تومان) <span class="text-rose-500">*</span></label>
                <input type="number" id="amount_toman" name="amount_toman" value="{{ old('amount_toman') }}" min="1" required placeholder="مثلاً: 15000000" class="input-text font-mono">
            </div>

            <div>
                <label for="payment_method" class="label">روش پرداخت <span class="text-rose-500">*</span></label>
                <select id="payment_method" name="payment_method" required class="input-text">
                    <option value="cash" @selected(old('payment_method') === 'cash')>نقدی</option>
                    <option value="pos" @selected(old('payment_method') === 'pos')>کارت‌خوان (POS)</option>
                    <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>انتقال بانکی / پایا / ساتنا</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="date" class="label">تاریخ سند <span class="text-rose-500">*</span></label>
                <input type="date" id="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="input-text font-mono">
            </div>

            <div>
                <label for="reference_number" class="label">شماره پیگیری / ارجاع بانکی</label>
                <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="شماره پیگیری یا فیش" dir="ltr" class="input-text font-mono">
            </div>
        </div>

        <div>
            <label for="notes" class="label">توضیحات و بابت</label>
            <textarea id="notes" name="notes" rows="2" class="input-text" placeholder="بابت تسویه فاکتور و...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('payments.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary {{ $type === 'receipt' ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-blue-700 hover:bg-blue-800' }}">
                ثبت سند مالی
            </button>
        </div>
    </form>
</div>
@endsection

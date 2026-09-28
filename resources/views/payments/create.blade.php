@extends('layouts.app')

@section('title', $type === 'receipt' ? 'ثبت دریافت وجه' : 'ثبت پرداخت وجه')
@section('page-heading', $type === 'receipt' ? 'ثبت سند دریافت وجه از مشتری' : 'ثبت سند پرداخت وجه به تأمین‌کننده')

@section('content')
<div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('payments.store') }}" class="space-y-6" x-data="{ method: @js(old('payment_method', 'cash')) }">
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
                <input type="text" inputmode="numeric" data-money-input id="amount_toman" name="amount_toman" value="{{ old('amount_toman') }}" min="1" :required="method !== 'endorsed_cheque'" placeholder="مثلاً: ۱۵,۰۰۰,۰۰۰" class="input-text font-mono">
            </div>

            <div>
                <label for="payment_method" class="label">روش پرداخت <span class="text-rose-500">*</span></label>
                <select id="payment_method" name="payment_method" required class="input-text" x-model="method">
                    <option value="cash" @selected(old('payment_method') === 'cash')>نقدی</option>
                    <option value="pos" @selected(old('payment_method') === 'pos')>کارت‌خوان (POS)</option>
                    <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>انتقال بانکی / پایا / ساتنا</option>
                    <option value="cheque" @selected(old('payment_method') === 'cheque')>{{ $type === 'receipt' ? 'دریافت چک' : 'صدور چک' }}</option>
                    @if($type === 'payment')<option value="endorsed_cheque" @selected(old('payment_method') === 'endorsed_cheque')>خرج‌کردن چک دریافتی</option>@endif
                </select>
            </div>
        </div>

        <div x-show="method === 'cheque'" x-cloak class="grid grid-cols-1 gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:grid-cols-2">
            <div><label class="label">شماره چک *</label><input name="check_number" value="{{ old('check_number') }}" class="input-text" :required="method === 'cheque'"></div>
            <div><label class="label">شناسه صیادی (۱۶ رقم)</label><input name="sayad_id" value="{{ old('sayad_id') }}" inputmode="numeric" maxlength="16" class="input-text font-mono"></div>
            <div><label class="label">نام بانک *</label><input name="bank_name" value="{{ old('bank_name') }}" class="input-text" :required="method === 'cheque'"></div>
            <div><label class="label">صاحب حساب</label><input name="account_owner" value="{{ old('account_owner') }}" class="input-text"></div>
            <div><label class="label">تاریخ سررسید *</label><input type="text" data-jdp autocomplete="off" name="due_date" value="{{ old('due_date') }}" class="input-text" :required="method === 'cheque'"></div>
        </div>

        @if($type === 'payment')
        <div x-show="method === 'endorsed_cheque'" x-cloak class="rounded-xl border border-teal-200 bg-teal-50 p-4">
            <label class="label">چک دریافتی موجود *</label>
            <select name="cheque_id" class="input-text" :required="method === 'endorsed_cheque'"><option value="">انتخاب چک برای خرج‌کردن...</option>@foreach($availableCheques as $cheque)<option value="{{ $cheque->id }}" @selected(old('cheque_id') == $cheque->id)>{{ $cheque->check_number }} — {{ $cheque->party?->name }} — {{ number_format($cheque->amount_rials / 10) }} تومان — سررسید {{ persian_date($cheque->due_date) }}</option>@endforeach</select>
            <p class="mt-2 text-xs text-slate-600">مبلغ پرداخت خودکار برابر مبلغ کامل چک انتخابی ثبت می‌شود.</p>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="date" class="label">تاریخ سند <span class="text-rose-500">*</span></label>
                <input type="text" data-jdp autocomplete="off" id="date" name="date" value="{{ persian_date(old('date', now())) }}" required class="input-text font-mono">
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

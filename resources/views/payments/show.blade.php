@extends('layouts.app')

@section('title', 'سند مالی: ' . $payment->payment_number)
@section('page-heading', 'سند مالی ' . $payment->payment_number)
@section('page-description', $payment->type === 'receipt' ? 'رسید دریافت وجه از مشتری' : 'سند پرداخت وجه به طرف‌حساب')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($payment->type === 'receipt')
                <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
                    دریافت وجه (بستانکاری مشتری / بدهکاری صندوق یا بانک)
                </span>
            @else
                <span class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 border border-blue-200">
                    پرداخت وجه (بدهکاری تأمین‌کننده / بستانکاری صندوق یا بانک)
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="button-secondary">چاپ سند</button>
            <a href="{{ route('payments.index') }}" class="button-secondary">بازگشت به فهرست</a>
        </div>
    </div>

    <div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div class="flex justify-between border-b border-slate-100 pb-4">
            <div>
                <span class="text-xs text-slate-500 block">شماره سند مالی:</span>
                <span class="font-mono font-bold text-base text-slate-900" dir="ltr">{{ $payment->payment_number }}</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">تاریخ سند:</span>
                <span class="font-mono text-sm text-slate-900" dir="ltr">{{ $payment->date->format('Y-m-d') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-xs text-slate-600">
            <div>
                <span class="text-slate-500 block">طرف‌حساب:</span>
                <span class="font-bold text-sm text-slate-900">{{ $payment->party?->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">حساب مالی:</span>
                <span class="font-semibold text-slate-900">{{ $payment->financialAccount?->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">روش پرداخت:</span>
                <span class="font-semibold text-slate-900">
                    @php
                        $methods = ['cash' => 'نقدی', 'pos' => 'کارت‌خوان', 'bank_transfer' => 'حواله / انتقال'];
                    @endphp
                    {{ $methods[$payment->payment_method] ?? $payment->payment_method }}
                </span>
            </div>
            <div>
                <span class="text-slate-500 block">شماره پیگیری / ارجاع:</span>
                <span class="font-mono text-slate-900" dir="ltr">{{ $payment->reference_number ?: '—' }}</span>
            </div>
        </div>

        <div class="border-t border-b border-slate-100 py-4 flex justify-between items-center bg-slate-50 p-4 rounded-lg">
            <span class="font-bold text-slate-800">مبلغ سند:</span>
            <span class="font-mono font-extrabold text-lg {{ $payment->type === 'receipt' ? 'text-emerald-700' : 'text-blue-700' }}">
                {{ number_format($payment->amount_rials / 10) }} تومان
            </span>
        </div>

        @if($payment->notes)
            <div class="text-xs text-slate-600">
                <span class="text-slate-500 block mb-1">بابت / توضیحات:</span>
                <p class="bg-white p-3 rounded border border-slate-100 text-slate-800">{{ $payment->notes }}</p>
            </div>
        @endif

        <div class="text-xs text-slate-400 pt-2 border-t border-slate-100 flex justify-between">
            <span>ثبت‌شده توسط: {{ $payment->creator?->name }}</span>
            <span>ثبت دوبل در دفتر کل: خودکار و متوازن</span>
        </div>
    </div>
</div>
@endsection

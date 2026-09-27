@extends('layouts.app')

@section('title', 'دریافت و پرداخت')
@section('page-heading', 'دریافت و پرداخت وجوه')
@section('page-description', 'مدیریت اسناد دریافت از مشتریان، پرداخت به تأمین‌کنندگان، صندوق و بانک')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('payments.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="شماره سند یا طرف‌حساب..."
                class="input-text max-w-xs font-mono"
            >
            <select name="type" class="input-text max-w-xs">
                <option value="">همه انواع تراکنش</option>
                <option value="receipt" @selected(request('type') === 'receipt')>دریافت از مشتری</option>
                <option value="payment" @selected(request('type') === 'payment')>پرداخت به تأمین‌کننده</option>
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->hasAny(['search', 'type']))
                <a href="{{ route('payments.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            @can('create', App\Models\Payment::class)
                <a href="{{ route('payments.create', ['type' => 'receipt']) }}" class="button-primary bg-emerald-700 hover:bg-emerald-800">
                    + ثبت دریافت وجه
                </a>
                <a href="{{ route('payments.create', ['type' => 'payment']) }}" class="button-primary bg-blue-700 hover:bg-blue-800">
                    - ثبت پرداخت وجه
                </a>
            @endcan
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">شماره سند</th>
                    <th class="p-4">نوع</th>
                    <th class="p-4">طرف‌حساب</th>
                    <th class="p-4">حساب صندوق / بانک</th>
                    <th class="p-4">روش پرداخت</th>
                    <th class="p-4">مبلغ (تومان)</th>
                    <th class="p-4">تاریخ</th>
                    <th class="p-4">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono font-bold text-slate-900" dir="ltr">
                            <a href="{{ route('payments.show', $payment) }}" class="text-teal-700 hover:underline">
                                {{ $payment->payment_number }}
                            </a>
                        </td>
                        <td class="p-4">
                            @if($payment->type === 'receipt')
                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">دریافت</span>
                            @else
                                <span class="rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">پرداخت</span>
                            @endif
                        </td>
                        <td class="p-4 font-semibold text-slate-800">
                            {{ $payment->party?->name }}
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $payment->financialAccount?->name }}
                        </td>
                        <td class="p-4 text-xs">
                            @php
                                $methodLabels = [
                                    'cash' => 'نقدی',
                                    'pos' => 'کارت‌خوان',
                                    'bank_transfer' => 'حواله / پایا',
                                ];
                            @endphp
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-slate-700 font-medium">
                                {{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}
                            </span>
                        </td>
                        <td class="p-4 font-bold font-mono {{ $payment->type === 'receipt' ? 'text-emerald-700' : 'text-blue-700' }}">
                            {{ number_format($payment->amount_rials / 10) }}
                        </td>
                        <td class="p-4 text-xs text-slate-600 font-mono" dir="ltr">
                            {{ persian_date($payment->date) }}
                        </td>
                        <td class="p-4">
                            <a href="{{ route('payments.show', $payment) }}" class="text-xs font-medium text-slate-600 hover:text-teal-700">رسید</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-500">هیچ تراکنش دریافت یا پرداختی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $payments->links() }}
    </div>
</div>
@endsection

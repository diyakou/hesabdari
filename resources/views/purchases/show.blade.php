@extends('layouts.app')

@section('title', 'فاکتور خرید: ' . $invoice->invoice_number)
@section('page-heading', 'فاکتور خرید ' . $invoice->invoice_number)
@section('page-description', 'وضعیت فاکتور: ' . ($invoice->status === 'finalized' ? 'نهایی‌شده' : 'پیش‌نویس'))

@section('content')
@if($invoice->purchasePaymentPlan)
    <section class="card mb-5 p-5">
        <div class="flex items-center justify-between"><h2 class="text-sm font-extrabold">شرایط پرداخت خرید</h2><span class="badge border-blue-200 bg-blue-50 text-blue-700">{{ $invoice->purchasePaymentPlan->payment_type === 'installment' ? 'اقساطی / چکی' : 'نقدی' }}</span></div>
        @if($invoice->purchasePaymentPlan->payment_type === 'installment')
            <p class="mt-3 text-xs text-slate-600">پیش‌پرداخت: <b>{{ number_format($invoice->purchasePaymentPlan->down_payment_rials / 10) }} تومان</b></p>
            <div class="mt-3 overflow-x-auto"><table class="w-full text-xs"><thead class="bg-slate-50"><tr><th class="p-2">شماره چک</th><th class="p-2">صیاد</th><th class="p-2">بانک</th><th class="p-2">مبلغ</th><th class="p-2">سررسید</th><th class="p-2">وضعیت</th></tr></thead><tbody>
            @foreach($invoice->purchasePaymentPlan->checks as $check)<tr class="border-t border-slate-100"><td class="p-2">{{ $check->check_number }}</td><td class="p-2 font-mono">{{ $check->sayad_id ?: '—' }}</td><td class="p-2">{{ $check->bank_name }}</td><td class="p-2">{{ number_format($check->amount_rials / 10) }} تومان</td><td class="p-2">{{ persian_date($check->due_date) }}</td><td class="p-2">در انتظار</td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>
@endif
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($invoice->status === 'finalized')
                <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
                    نهایی‌شده (سند دوبل صادر شد)
                </span>
            @else
                <span class="rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 border border-amber-200">
                    پیش‌نویس (فاقد اثر مالی و انبار)
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if($invoice->status === 'draft')
                @can('update', $invoice)
                    <form method="POST" action="{{ route('purchases.finalize', $invoice) }}" onsubmit="return confirm('آیا از نهایی‌سازی این فاکتور خرید و ورود اقلام به انبار اطمینان دارید؟');">
                        @csrf
                        <button type="submit" class="button-primary bg-emerald-700 hover:bg-emerald-800">
                            نهایی‌سازی فاکتور و ثبت در انبار
                        </button>
                    </form>
                @endcan
            @endif
            <a href="{{ route('purchases.index') }}" class="button-secondary">بازگشت به فهرست</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-2 text-xs text-slate-600">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">مشخصات طرف‌حساب و انبار</h3>
            <div class="flex justify-between">
                <span class="text-slate-500">تأمین‌کننده:</span>
                <span class="font-semibold text-slate-900">{{ $invoice->party?->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">انبار مقصد:</span>
                <span>{{ $invoice->warehouse?->name ?: '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">تاریخ فاکتور:</span>
                <span class="font-mono">{{ persian_date($invoice->issue_date) }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-2 text-xs text-slate-600">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">مبالغ و هزینه‌ها (تومان)</h3>
            <div class="flex justify-between">
                <span class="text-slate-500">مجموع ناخالص:</span>
                <span class="font-mono">{{ number_format($invoice->subtotal_rials / 10) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">تخفیف کل:</span>
                <span class="font-mono text-rose-600">{{ number_format($invoice->discount_rials / 10) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">هزینه جانبی (Landed Cost):</span>
                <span class="font-mono text-amber-600">{{ number_format($invoice->additional_cost_rials / 10) }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-100 pt-2 font-bold text-sm text-teal-700">
                <span>مبلغ کل قابل پرداخت:</span>
                <span class="font-mono">{{ number_format($invoice->total_amount_rials / 10) }} تومان</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-2 text-xs text-slate-600">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">اطلاعات سیستمی</h3>
            <div class="flex justify-between">
                <span class="text-slate-500">ثبت‌کننده:</span>
                <span>{{ $invoice->creator?->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block mb-1">یادداشت:</span>
                <p class="text-slate-800">{{ $invoice->notes ?: '—' }}</p>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">ردیف</th>
                    <th class="p-4">کالا و تنوع</th>
                    <th class="p-4">تعداد</th>
                    <th class="p-4">قیمت واحد (تومان)</th>
                    <th class="p-4">تخفیف (تومان)</th>
                    <th class="p-4">هزینه سرشکن (تومان)</th>
                    <th class="p-4">بهای تمام‌شده واحد (تومان)</th>
                    <th class="p-4">مبلغ خالص (تومان)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($invoice->lines as $index => $line)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 text-xs font-mono">{{ $index + 1 }}</td>
                        <td class="p-4 font-semibold text-slate-900">
                            {{ $line->product?->name }} - {{ $line->variant?->display_name }}
                            @if($line->device)
                                <span class="block text-xs font-mono text-teal-700" dir="ltr">
                                    IMEI: {{ $line->device->primary_imei }}
                                </span>
                            @endif
                        </td>
                        <td class="p-4 font-mono">{{ $line->quantity }}</td>
                        <td class="p-4 font-mono">{{ number_format($line->unit_price_rials / 10) }}</td>
                        <td class="p-4 font-mono text-xs text-rose-600">{{ number_format($line->discount_rials / 10) }}</td>
                        <td class="p-4 font-mono text-xs text-amber-600">{{ number_format($line->allocated_cost_rials / 10) }}</td>
                        <td class="p-4 font-mono text-xs font-bold text-slate-900">
                            {{ $line->snapshot_cost_rials ? number_format($line->snapshot_cost_rials / 10) : '—' }}
                        </td>
                        <td class="p-4 font-mono font-bold text-teal-700">
                            {{ number_format((($line->quantity * $line->unit_price_rials) - $line->discount_rials + $line->allocated_cost_rials) / 10) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

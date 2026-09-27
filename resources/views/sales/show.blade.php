@extends('layouts.app')

@section('title', 'فاکتور فروش: ' . $invoice->invoice_number)
@section('page-heading', 'فاکتور فروش ' . $invoice->invoice_number)
@section('page-description', 'وضعیت: ' . ($invoice->status === 'finalized' ? 'نهایی‌شده' : 'پیش‌نویس'))

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($invoice->status === 'finalized')
                <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
                    نهایی‌شده (موجودی کسر شد و سند درآمد صادر گردید)
                </span>
            @else
                <span class="rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 border border-amber-200">
                    پیش‌نویس / پیش‌فاکتور (بدون اثر مالی و انبار)
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('sales.print', ['invoice' => $invoice, 'format' => 'a4']) }}" target="_blank" class="button-secondary">
                🖨️ چاپ A4 مشتری
            </a>
            <a href="{{ route('sales.print', ['invoice' => $invoice, 'format' => 'thermal']) }}" target="_blank" class="button-secondary">
                🧾 چاپ حرارتی ۸۰mm
            </a>
            <a href="{{ route('sales.index') }}" class="button-secondary">بازگشت به فهرست</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-2 text-xs text-slate-600">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">اطلاعات مشتری و فاکتور</h3>
            <div class="flex justify-between">
                <span class="text-slate-500">نام مشتری:</span>
                <span class="font-semibold text-slate-900">{{ $invoice->party?->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">شماره موبایل:</span>
                <span class="font-mono" dir="ltr">{{ $invoice->party?->mobile ?: '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">تاریخ صدور:</span>
                <span class="font-mono" dir="ltr">{{ $invoice->issue_date->format('Y-m-d') }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-2 text-xs text-slate-600">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">مبالغ و پرداخت‌ها (تومان)</h3>
            <div class="flex justify-between">
                <span class="text-slate-500">جمع ناخالص اقلام:</span>
                <span class="font-mono">{{ number_format($invoice->subtotal_rials / 10) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">تخفیف کل:</span>
                <span class="font-mono text-rose-600">{{ number_format($invoice->discount_rials / 10) }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-100 pt-2 font-bold text-sm text-teal-700">
                <span>مبلغ قابل پرداخت:</span>
                <span class="font-mono">{{ number_format($invoice->total_amount_rials / 10) }} تومان</span>
            </div>
            @php
                $paidRials = $invoice->allocations->sum('amount_rials');
                $remainingRials = max(0, $invoice->total_amount_rials - $paidRials);
            @endphp
            <div class="flex justify-between text-slate-700 pt-1 border-t border-slate-100">
                <span>مبلغ تسویه شده:</span>
                <span class="font-mono font-bold text-emerald-600">{{ number_format($paidRials / 10) }} تومان</span>
            </div>
            <div class="flex justify-between text-slate-700">
                <span>مانده بدهی این فاکتور:</span>
                <span class="font-mono font-bold {{ $remainingRials > 0 ? 'text-amber-600' : 'text-slate-500' }}">
                    {{ number_format($remainingRials / 10) }} تومان
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-2 text-xs text-slate-600">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">اطلاعات سیستمی</h3>
            <div class="flex justify-between">
                <span class="text-slate-500">انبار مبدأ:</span>
                <span>{{ $invoice->warehouse?->name ?: '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">کاربر ثبت‌کننده:</span>
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
                    <th class="p-4">کالا / خدمت</th>
                    <th class="p-4">تعداد</th>
                    <th class="p-4">قیمت واحد (تومان)</th>
                    <th class="p-4">تخفیف (تومان)</th>
                    <th class="p-4">مبلغ نهایی ردیف (تومان)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($invoice->lines as $index => $line)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 text-xs font-mono">{{ $index + 1 }}</td>
                        <td class="p-4 font-semibold text-slate-900">
                            {{ $line->product?->name }}
                            @if($line->variant?->color || $line->variant?->storage)
                                <span class="text-xs text-slate-500">({{ $line->variant->color }} - {{ $line->variant->storage }})</span>
                            @endif
                            @if($line->device)
                                <span class="block text-xs font-mono text-teal-700" dir="ltr">
                                    IMEI: {{ $line->device->primary_imei }}
                                </span>
                            @endif
                        </td>
                        <td class="p-4 font-mono">{{ $line->quantity }}</td>
                        <td class="p-4 font-mono">{{ number_format($line->unit_price_rials / 10) }}</td>
                        <td class="p-4 font-mono text-xs text-rose-600">{{ number_format($line->discount_rials / 10) }}</td>
                        <td class="p-4 font-mono font-bold text-teal-700">
                            {{ number_format((($line->quantity * $line->unit_price_rials) - $line->discount_rials) / 10) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

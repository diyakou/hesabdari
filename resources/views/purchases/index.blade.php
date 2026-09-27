@extends('layouts.app')

@section('title', 'فاکتورهای خرید')
@section('page-heading', 'فاکتورهای خرید')
@section('page-description', 'مدیریت خرید کالا و گوشی از تأمین‌کنندگان، ثبت هزینه‌های جانبی و ورود به انبار')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('purchases.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="شماره فاکتور یا نام تأمین‌کننده..."
                class="input-text max-w-xs font-mono"
            >
            <select name="status" class="input-text max-w-xs">
                <option value="">همه وضعیت‌ها</option>
                <option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option>
                <option value="finalized" @selected(request('status') === 'finalized')>نهایی‌شده</option>
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('purchases.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        @can('create', App\Models\Invoice::class)
            <a href="{{ route('purchases.create') }}" class="button-primary">
                + ثبت فاکتور خرید جدید
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">شماره فاکتور</th>
                    <th class="p-4">تأمین‌کننده</th>
                    <th class="p-4">تاریخ صدور</th>
                    <th class="p-4">انبار مقصد</th>
                    <th class="p-4">هزینه جانبی (تومان)</th>
                    <th class="p-4">مبلغ کل (تومان)</th>
                    <th class="p-4">وضعیت</th>
                    <th class="p-4">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($purchases as $purchase)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono font-bold text-slate-900" dir="ltr">
                            <a href="{{ route('purchases.show', $purchase) }}" class="text-teal-700 hover:underline">
                                {{ $purchase->invoice_number }}
                            </a>
                        </td>
                        <td class="p-4 font-semibold text-slate-800">
                            {{ $purchase->party?->name }}
                        </td>
                        <td class="p-4 text-xs text-slate-600 font-mono" dir="ltr">
                            {{ $purchase->issue_date->format('Y-m-d') }}
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $purchase->warehouse?->name ?: '—' }}
                        </td>
                        <td class="p-4 text-xs font-mono">
                            {{ $purchase->additional_cost_rials ? number_format($purchase->additional_cost_rials / 10) : '۰' }}
                        </td>
                        <td class="p-4 font-semibold text-teal-700 font-mono">
                            {{ number_format($purchase->total_amount_rials / 10) }}
                        </td>
                        <td class="p-4">
                            @if($purchase->status === 'finalized')
                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">نهایی‌شده</span>
                            @else
                                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">پیش‌نویس</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <a href="{{ route('purchases.show', $purchase) }}" class="text-xs font-medium text-slate-600 hover:text-teal-700">مشاهده</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-500">هیچ فاکتور خریدی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $purchases->links() }}
    </div>
</div>
@endsection

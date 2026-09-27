@extends('layouts.app')

@section('title', 'فاکتورهای فروش')
@section('page-heading', 'فاکتورهای فروش (میز فروش)')
@section('page-description', 'مدیریت فروش کالا، گوشی‌های دارای IMEI و خدمات با تسویه چندوجهی')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('sales.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="شماره فاکتور یا نام مشتری..."
                class="input-text max-w-xs font-mono"
            >
            <select name="status" class="input-text max-w-xs">
                <option value="">همه وضعیت‌ها</option>
                <option value="finalized" @selected(request('status') === 'finalized')>نهایی‌شده</option>
                <option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option>
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('sales.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        @can('create', App\Models\Invoice::class)
            <a href="{{ route('sales.create') }}" class="button-primary bg-teal-700 hover:bg-teal-800">
                + صدور فاکتور فروش جدید
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">شماره فاکتور</th>
                    <th class="p-4">مشتری</th>
                    <th class="p-4">تاریخ صدور</th>
                    <th class="p-4">انبار</th>
                    <th class="p-4">مبلغ ناخالص (تومان)</th>
                    <th class="p-4">تخفیف (تومان)</th>
                    <th class="p-4">مبلغ کل (تومان)</th>
                    <th class="p-4">وضعیت</th>
                    <th class="p-4">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($sales as $sale)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono font-bold text-slate-900" dir="ltr">
                            <a href="{{ route('sales.show', $sale) }}" class="text-teal-700 hover:underline">
                                {{ $sale->invoice_number }}
                            </a>
                        </td>
                        <td class="p-4 font-semibold text-slate-800">
                            {{ $sale->party?->name }}
                        </td>
                        <td class="p-4 text-xs text-slate-600 font-mono" dir="ltr">
                            {{ persian_date($sale->issue_date) }}
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $sale->warehouse?->name ?: '—' }}
                        </td>
                        <td class="p-4 text-xs font-mono">
                            {{ number_format($sale->subtotal_rials / 10) }}
                        </td>
                        <td class="p-4 text-xs font-mono text-rose-600">
                            {{ $sale->discount_rials ? number_format($sale->discount_rials / 10) : '۰' }}
                        </td>
                        <td class="p-4 font-bold font-mono text-teal-700">
                            {{ number_format($sale->total_amount_rials / 10) }}
                        </td>
                        <td class="p-4">
                            @if($sale->status === 'finalized')
                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">نهایی‌شده</span>
                            @else
                                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 border border-amber-200">پیش‌نویس</span>
                            @endif
                        </td>
                        <td class="p-4 flex items-center gap-2">
                            <a href="{{ route('sales.show', $sale) }}" class="text-xs font-medium text-slate-600 hover:text-teal-700">مشاهده</a>
                            <a href="{{ route('sales.print', $sale) }}" class="text-xs font-medium text-slate-600 hover:text-teal-700">چاپ</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center text-slate-500">هیچ فاکتور فروشی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $sales->links() }}
    </div>
</div>
@endsection

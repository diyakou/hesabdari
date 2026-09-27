@extends('reports.layout')

@section('title', 'گزارش فروش و سود ناخالص')
@section('page-heading', 'گزارش فروش، بهای تمام‌شده و سود ناخالص')
@section('page-description', 'تحلیل عملکرد فروش کالا و گوشی، بهای تمام‌شده واقعی (COGS) و حاشیه برآوردی خدمات')

@section('report_content')
<div class="space-y-6">
    <!-- Filters & Export -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl bg-white p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500">از تاریخ:</label>
                <input type="text" data-jdp autocomplete="off" name="start_date" value="{{ $startDate ? persian_date($startDate) : '' }}" class="input-text font-mono text-xs">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500">تا تاریخ:</label>
                <input type="text" data-jdp autocomplete="off" name="end_date" value="{{ $endDate ? persian_date($endDate) : '' }}" class="input-text font-mono text-xs">
            </div>
            <button type="submit" class="button-primary text-xs">اعمال فیلتر بازه</button>
            @if($startDate || $endDate)
                <a href="{{ route('reports.index') }}" class="button-secondary text-xs">پاک کردن</a>
            @endif
        </form>

        <a href="{{ route('reports.export', ['type' => 'sales', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="button-secondary text-xs flex items-center gap-2">
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
            </svg>
            دریافت خروجی امن Excel (CSV)
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs text-slate-500 block">فروش خالص کل (تومان)</span>
            <span class="text-xl font-bold text-slate-900 font-mono mt-1 block">
                {{ number_format($summary['total_net_sales_rials'] / 10) }}
            </span>
            <span class="text-xs text-slate-400 mt-2 block">{{ $summary['total_invoices_count'] }} فاکتور فروش ({{ $summary['total_returns_count'] }} مرجوعی)</span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs text-slate-500 block">سود ناخالص کالایی (تومان)</span>
            <span class="text-xl font-bold text-emerald-700 font-mono mt-1 block">
                {{ number_format($summary['goods_gross_profit_rials'] / 10) }}
            </span>
            <span class="text-xs text-slate-500 mt-2 block">
                حاشیه سود: <strong>{{ $summary['goods_margin_percent'] !== null ? $summary['goods_margin_percent'] . '%' : 'نامعین' }}</strong>
            </span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs text-slate-500 block">بهای تمام‌شده کالای فروش‌رفته (COGS)</span>
            <span class="text-xl font-bold text-rose-700 font-mono mt-1 block">
                {{ number_format($summary['goods_cogs_rials'] / 10) }}
            </span>
            <span class="text-xs text-slate-400 mt-2 block">بر مبنای اسنپ‌شات قطعی خروج</span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs text-slate-500 block">حاشیه برآوردی خدمات (تومان)</span>
            <span class="text-xl font-bold text-teal-700 font-mono mt-1 block">
                {{ number_format($summary['services_margin_rials'] / 10) }}
            </span>
            <span class="text-xs text-slate-500 mt-2 block">
                درآمد: {{ number_format($summary['services_sales_rials'] / 10) }} تومان
            </span>
        </div>
    </div>

    <!-- Details Table -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">تفکیک جامع جریان فروش و سودآوری</h3>

        <table class="w-full text-right text-sm">
            <tbody class="divide-y divide-slate-100">
                <tr class="hover:bg-slate-50">
                    <td class="p-3 text-slate-600">فروش خالص اقلام کالایی (گوشی و لوازم جانبی)</td>
                    <td class="p-3 font-mono font-bold text-slate-900 text-left" dir="ltr">
                        {{ number_format($summary['goods_sales_rials'] / 10) }} تومان
                    </td>
                </tr>
                <tr class="hover:bg-slate-50">
                    <td class="p-3 text-slate-600">بهای تمام‌شده خروج کالاهای فوق (COGS)</td>
                    <td class="p-3 font-mono text-rose-700 text-left" dir="ltr">
                        ({{ number_format($summary['goods_cogs_rials'] / 10) }}) تومان
                    </td>
                </tr>
                <tr class="bg-emerald-50/50 font-bold">
                    <td class="p-3 text-emerald-900">سود ناخالص فروش کالا</td>
                    <td class="p-3 font-mono text-emerald-800 text-left" dir="ltr">
                        {{ number_format($summary['goods_gross_profit_rials'] / 10) }} تومان
                    </td>
                </tr>
                <tr class="hover:bg-slate-50">
                    <td class="p-3 text-slate-600">درآمد حاصل از ارائه خدمات فنی و نرم‌افزاری</td>
                    <td class="p-3 font-mono font-semibold text-slate-900 text-left" dir="ltr">
                        {{ number_format($summary['services_sales_rials'] / 10) }} تومان
                    </td>
                </tr>
                <tr class="hover:bg-slate-50">
                    <td class="p-3 text-slate-600">هزینه‌های مستقیم خدمات (خرید اکانت، قطعه تعویضی و...)</td>
                    <td class="p-3 font-mono text-slate-600 text-left" dir="ltr">
                        ({{ number_format($summary['services_direct_cost_rials'] / 10) }}) تومان
                    </td>
                </tr>
                <tr class="bg-teal-50/50 font-bold">
                    <td class="p-3 text-teal-900">حاشیه ناخالص برآوردی خدمات (خدمت فاقد موجودی انبار است)</td>
                    <td class="p-3 font-mono text-teal-800 text-left" dir="ltr">
                        {{ number_format($summary['services_margin_rials'] / 10) }} تومان
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

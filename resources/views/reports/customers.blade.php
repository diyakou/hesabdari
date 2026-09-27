@extends('reports.layout')

@section('title', 'تحلیل و طبقه‌بندی مشتریان')
@section('page-heading', 'تحلیل و طبقه‌بندی رفتار مشتریان')
@section('page-description', 'تفکیک مشتریان فعال، مشتریان غیرفعال (بیش از ۹۰ روز بدون خرید)، اشخاص بدون خرید و رتبه‌بندی سودآوری')

@section('report_content')
<div class="space-y-6">
    <!-- Group Count Summary -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm">
            <span class="text-xs font-bold text-emerald-800 block">مشتریان فعال</span>
            <span class="text-2xl font-bold text-emerald-900 font-mono mt-1 block">
                {{ $classification['active_customers']->count() }}
            </span>
            <span class="text-xs text-emerald-700 mt-2 block">دارای خرید در {{ $threshold }} روز اخیر</span>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm">
            <span class="text-xs font-bold text-amber-800 block">مشتریان غیرفعال (ریزش‌کرده)</span>
            <span class="text-2xl font-bold text-amber-900 font-mono mt-1 block">
                {{ $classification['dormant_customers']->count() }}
            </span>
            <span class="text-xs text-amber-700 mt-2 block">سابقه خرید دارند ولی بیش از {{ $threshold }} روز خریدی نکرده‌اند</span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-700 block">اشخاص ثبت‌شده بدون خرید</span>
            <span class="text-2xl font-bold text-slate-900 font-mono mt-1 block">
                {{ $classification['non_purchasing_customers']->count() }}
            </span>
            <span class="text-xs text-slate-500 mt-2 block">حساب ثبت‌شده دارند اما تاکنون فاکتور خریدی ثبت نکرده‌اند</span>
        </div>
    </div>

    <!-- Top profitable customers -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">سودده‌ترین مشتریان (بر مبنای سود ناخالص کالایی)</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                    <tr>
                        <th class="p-3">مشتری</th>
                        <th class="p-3">شماره تماس</th>
                        <th class="p-3">تعداد فاکتور</th>
                        <th class="p-3">آخرین خرید</th>
                        <th class="p-3">حجم کل خرید (تومان)</th>
                        <th class="p-3">سود ناخالص حاصله (تومان)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($classification['top_customers'] as $c)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 font-semibold text-slate-900">
                                <a href="{{ route('parties.show', $c['customer_id']) }}" class="text-teal-700 hover:underline">
                                    {{ $c['customer_name'] }}
                                </a>
                            </td>
                            <td class="p-3 font-mono text-xs text-slate-600" dir="ltr">
                                {{ $c['phone'] ?: '—' }}
                            </td>
                            <td class="p-3 font-mono text-slate-800">
                                {{ $c['invoices_count'] }}
                            </td>
                            <td class="p-3 font-mono text-xs text-slate-600" dir="ltr">
                                {{ $c['last_purchase_date'] }}
                            </td>
                            <td class="p-3 font-mono text-slate-900">
                                {{ number_format($c['total_sales_rials'] / 10) }}
                            </td>
                            <td class="p-3 font-mono font-bold text-emerald-700">
                                {{ number_format($c['gross_profit_rials'] / 10) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-500">هیچ سابقه خریدی برای محاسبه سودآوری وجود ندارد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

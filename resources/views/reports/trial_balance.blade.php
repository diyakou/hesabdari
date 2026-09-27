@extends('reports.layout')

@section('title', 'تراز آزمایشی و توازن دفتر کل')
@section('page-heading', 'تراز آزمایشی و کنترل توازن دفتر کل')
@section('page-description', 'صورت مانده‌گیری کل حساب‌های دفتر کل با ثبت دوبل خودکار داخلی')

@section('report_content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl bg-white p-4 border border-slate-200 shadow-sm">
        <div class="flex items-center gap-3">
            @if($trialBalance['is_balanced'])
                <span class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 border border-emerald-200 flex items-center gap-1.5">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    دفتر کل کاملاً متوازن است (جمع بدهکار = جمع بستانکار)
                </span>
            @else
                <span class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-800 border border-rose-200">
                    هشدار: عدم توازن در اسناد مالی!
                </span>
            @endif
        </div>

        <a href="{{ route('reports.export', ['type' => 'trial_balance']) }}" class="button-secondary text-xs flex items-center gap-2">
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
            </svg>
            دریافت خروجی امن Excel (CSV)
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">کد حساب</th>
                    <th class="p-4">نام حساب دفتر کل</th>
                    <th class="p-4">ماهیت</th>
                    <th class="p-4">گردش بدهکار (تومان)</th>
                    <th class="p-4">گردش بستانکار (تومان)</th>
                    <th class="p-4">مانده حساب (تومان)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($trialBalance['accounts'] as $acc)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono font-bold text-slate-900" dir="ltr">
                            {{ $acc['code'] }}
                        </td>
                        <td class="p-4 font-semibold text-slate-800">
                            {{ $acc['name'] }}
                        </td>
                        <td class="p-4 text-xs text-slate-500">
                            @php
                                $typeLabels = [
                                    'asset' => 'دارایی',
                                    'liability' => 'بدهی',
                                    'equity' => 'سرمایه',
                                    'revenue' => 'درآمد',
                                    'expense' => 'هزینه / بها',
                                ];
                            @endphp
                            {{ $typeLabels[$acc['type']] ?? $acc['type'] }}
                        </td>
                        <td class="p-4 font-mono text-slate-800">
                            {{ number_format($acc['debit_rials'] / 10) }}
                        </td>
                        <td class="p-4 font-mono text-slate-800">
                            {{ number_format($acc['credit_rials'] / 10) }}
                        </td>
                        <td class="p-4 font-mono font-bold {{ $acc['balance_rials'] < 0 ? 'text-rose-700' : 'text-slate-900' }}">
                            {{ number_format($acc['balance_rials'] / 10) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-slate-300 bg-slate-50 font-bold">
                <tr>
                    <td colspan="3" class="p-4 text-slate-800">جمع کل تراز:</td>
                    <td class="p-4 font-mono text-slate-900 font-bold" dir="ltr">
                        {{ number_format($trialBalance['total_debits_rials'] / 10) }} تومان
                    </td>
                    <td class="p-4 font-mono text-slate-900 font-bold" dir="ltr">
                        {{ number_format($trialBalance['total_credits_rials'] / 10) }} تومان
                    </td>
                    <td class="p-4 font-mono font-bold text-teal-700" dir="ltr">
                        تفاضل: {{ number_format(($trialBalance['total_debits_rials'] - $trialBalance['total_credits_rials']) / 10) }} تومان
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

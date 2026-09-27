@extends('reports.layout')

@section('title', 'ارزش موجودی و تطبیق انبار')
@section('page-heading', 'ارزش‌گذاری موجودی انبار و تطبیق دفتری')
@section('page-description', 'محاسبه ارزش دفتری موجودی کالا (شامل قرنطینه، فاقد گوشی‌های امانی تعمیری)، تطبیق با حساب ۱۰۳ دفتر کل و کالاهای راکد')

@section('report_content')
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs text-slate-500 block">ارزش دفتری اقلام انبار (تومان)</span>
            <span class="text-xl font-bold text-slate-900 font-mono mt-1 block">
                {{ number_format($valuation['stock_items_value_rials'] / 10) }}
            </span>
            <span class="text-xs text-slate-400 mt-2 block">محاسبه‌شده بر مبنای میانگین موزون متحرک</span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs text-slate-500 block">دستگاه‌های سریالی در تملک فروشگاه</span>
            <span class="text-xl font-bold text-teal-700 font-mono mt-1 block">
                {{ $valuation['devices_count'] }} دستگاه
            </span>
            <span class="text-xs text-slate-500 mt-2 block">
                شامل {{ $valuation['quarantine_devices_count'] }} دستگاه در قرنطینه فنی (فاقد گوشی‌های مشتری)
            </span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs text-slate-500 block">مانده حساب موجودی کالا در دفتر کل (حساب ۱۰۳)</span>
            <span class="text-xl font-bold text-slate-900 font-mono mt-1 block">
                {{ number_format($valuation['gl_account_103_balance_rials'] / 10) }} تومان
            </span>
            <span class="text-xs mt-2 block">
                @if($valuation['is_reconciled'])
                    <span class="text-emerald-700 font-bold">✓ ارزش انبار و حساب دفتر کل کاملاً منطبق است</span>
                @else
                    <span class="text-amber-600 font-bold">مغایرت نیازمند بررسی</span>
                @endif
            </span>
        </div>
    </div>

    <!-- Idle Stock Section -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">کالاهای راکد انبار (عدم گردش بیش از ۴۵ روز)</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                    <tr>
                        <th class="p-3">عنوان کالا</th>
                        <th class="p-3">انبار</th>
                        <th class="p-3">تعداد مانده</th>
                        <th class="p-3">ارزش دفتری (تومان)</th>
                        <th class="p-3">آخرین گردش</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($valuation['idle_stocks'] as $idle)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 font-semibold text-slate-800">
                                {{ $idle->productVariant?->product?->name }} ({{ $idle->productVariant?->variant_name }})
                            </td>
                            <td class="p-3 text-xs text-slate-600">
                                {{ $idle->warehouse?->name }}
                            </td>
                            <td class="p-3 font-mono font-bold text-slate-900">
                                {{ $idle->quantity }}
                            </td>
                            <td class="p-3 font-mono text-slate-800">
                                {{ number_format($idle->total_cost_rials / 10) }}
                            </td>
                            <td class="p-3 font-mono text-xs text-slate-500" dir="ltr">
                                {{ persian_date($idle->updated_at) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-500">هیچ کالای راکدی یافت نشد. گردش انبار مطلوب است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

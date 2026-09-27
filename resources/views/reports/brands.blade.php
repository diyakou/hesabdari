@extends('reports.layout')

@section('title', 'عملکرد و سودآوری برندها')
@section('page-heading', 'تحلیل عملکرد و سودآوری برندها')
@section('page-description', 'رتبه‌بندی برندها بر اساس حجم فروش خالص، تعداد دستگاه/کالای فروخته‌شده و سود ناخالص ریالی')

@section('report_content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl bg-white p-4 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('reports.brands') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500">از تاریخ:</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="input-text font-mono text-xs" dir="ltr">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500">تا تاریخ:</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="input-text font-mono text-xs" dir="ltr">
            </div>
            <button type="submit" class="button-primary text-xs">اعمال فیلتر بازه</button>
            @if($startDate || $endDate)
                <a href="{{ route('reports.brands') }}" class="button-secondary text-xs">پاک کردن</a>
            @endif
        </form>

        <a href="{{ route('reports.export', ['type' => 'brands', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="button-secondary text-xs flex items-center gap-2">
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
                    <th class="p-4">رتبه</th>
                    <th class="p-4">نام برند</th>
                    <th class="p-4">تعداد خالص فروش</th>
                    <th class="p-4">فروش خالص (تومان)</th>
                    <th class="p-4">بهای تمام‌شده (COGS)</th>
                    <th class="p-4">سود ناخالص (تومان)</th>
                    <th class="p-4">حاشیه سود</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($brands as $index => $brand)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono font-bold text-slate-400">
                            {{ $index + 1 }}
                        </td>
                        <td class="p-4 font-bold text-slate-900">
                            {{ $brand['brand_name'] }}
                        </td>
                        <td class="p-4 font-mono font-bold text-slate-800">
                            {{ number_format($brand['net_quantity']) }}
                        </td>
                        <td class="p-4 font-mono text-slate-900">
                            {{ number_format($brand['net_sales_rials'] / 10) }}
                        </td>
                        <td class="p-4 font-mono text-xs text-rose-700">
                            {{ number_format($brand['goods_cogs_rials'] / 10) }}
                        </td>
                        <td class="p-4 font-mono font-bold text-emerald-700">
                            {{ number_format($brand['gross_profit_rials'] / 10) }}
                        </td>
                        <td class="p-4 text-xs font-mono font-semibold">
                            {{ $brand['margin_percent'] !== null ? $brand['margin_percent'] . '%' : 'نامعین' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-500">هیچ داده فروشی برای برندها در این بازه ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

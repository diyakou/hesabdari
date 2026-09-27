@extends('layouts.app')

@section('title', 'موجودی و انبارگردانی')
@section('page-heading', 'مدیریت موجودی انبار و تعدیلات')
@section('page-description', 'مشاهده موجودی کالاهای غیراختصاصی، میانگین موزون بهای تمام‌شده و ثبت مغایرت‌های انبارگردانی')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-base font-bold text-slate-800">موجودی فعلی اقلام کالایی</h2>
        @if(auth()->user()->isManager())
            <a href="{{ route('inventory.adjust') }}" class="button-primary">
                + ثبت تعدیل انبارگردانی (کسری / اضافات)
            </a>
        @endif
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">عنوان کالا / تنوع</th>
                    <th class="p-4">انبار</th>
                    <th class="p-4">موجودی فیزیکی</th>
                    <th class="p-4">میانگین بهای تمام‌شده (تومان)</th>
                    <th class="p-4">ارزش کل موجودی (تومان)</th>
                    <th class="p-4">قیمت فروش مصوب (تومان)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($stocks as $stock)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4">
                            <span class="font-semibold text-slate-900 block">
                                {{ $stock->productVariant?->product?->name }}
                            </span>
                            @if($stock->productVariant?->variant_name)
                                <span class="text-xs text-slate-500">{{ $stock->productVariant->variant_name }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $stock->warehouse?->name }}
                        </td>
                        <td class="p-4 font-mono font-bold text-slate-900">
                            {{ number_format($stock->quantity) }}
                        </td>
                        <td class="p-4 font-mono text-xs">
                            @can('canSeeFinancials', App\Models\User::class)
                                {{ number_format($stock->average_unit_cost / 10) }}
                            @else
                                <span class="text-slate-400">محرمانه</span>
                            @endcan
                        </td>
                        <td class="p-4 font-mono font-semibold text-teal-700">
                            @can('canSeeFinancials', App\Models\User::class)
                                {{ number_format($stock->total_cost_rials / 10) }}
                            @else
                                <span class="text-slate-400">محرمانه</span>
                            @endcan
                        </td>
                        <td class="p-4 font-mono text-xs text-slate-800">
                            {{ number_format(($stock->productVariant?->selling_price_rials ?? 0) / 10) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-500">هیچ موجودی کالایی در این انبار ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $stocks->links() }}
    </div>

    <!-- Latest adjustments -->
    @if($adjustments->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">آخرین تعدیلات و صورت‌جلسات انبارگردانی</h3>
            <div class="divide-y divide-slate-100 text-xs">
                @foreach($adjustments as $adj)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800">{{ $adj->productVariant?->display_name }}</span>
                            <span class="text-slate-500 mr-2">({{ $adj->warehouse?->name }})</span>
                            <span class="text-slate-600 mr-2">— {{ $adj->notes }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono font-bold {{ $adj->quantity_change > 0 ? 'text-emerald-700' : 'text-rose-700' }}" dir="ltr">
                                {{ $adj->quantity_change > 0 ? '+' : '' }}{{ $adj->quantity_change }}
                            </span>
                            <span class="text-slate-400 font-mono" dir="ltr">{{ $adj->created_at->format('Y-m-d H:i') }}</span>
                            <span class="text-slate-500">ثبت: {{ $adj->creator?->name }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

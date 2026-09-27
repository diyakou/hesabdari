@extends('layouts.app')

@section('title', 'انتقال داخلی وجوه')
@section('page-heading', 'انتقال داخلی وجوه')
@section('page-description', 'جابجایی نقدینگی بین صندوق‌ها، حساب‌های بانکی و دستگاه‌های کارتخوان')

@section('content')
<div class="space-y-6">
    <div class="flex justify-end">
        <a href="{{ route('transfers.create') }}" class="button-primary">
            + ثبت انتقال وجه جدید
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">شماره سند</th>
                    <th class="p-4">حساب مبدأ (برداشت)</th>
                    <th class="p-4">حساب مقصد (واریز)</th>
                    <th class="p-4">تاریخ</th>
                    <th class="p-4">مبلغ (تومان)</th>
                    <th class="p-4">شماره پیگیری / ارجاع</th>
                    <th class="p-4">یادداشت</th>
                    <th class="p-4">ثبت‌کننده</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transfers as $transfer)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono font-bold text-slate-900" dir="ltr">
                            {{ $transfer->transfer_number }}
                        </td>
                        <td class="p-4 font-semibold text-rose-700">
                            {{ $transfer->sourceAccount?->name }}
                        </td>
                        <td class="p-4 font-semibold text-emerald-700">
                            {{ $transfer->destinationAccount?->name }}
                        </td>
                        <td class="p-4 text-xs text-slate-600 font-mono" dir="ltr">
                            {{ persian_date($transfer->date) }}
                        </td>
                        <td class="p-4 font-semibold text-slate-900 font-mono">
                            {{ number_format($transfer->amount_rials / 10) }}
                        </td>
                        <td class="p-4 text-xs font-mono text-slate-600" dir="ltr">
                            {{ $transfer->tracking_number ?: '—' }}
                        </td>
                        <td class="p-4 text-xs text-slate-500 max-w-xs truncate">
                            {{ $transfer->notes ?: '—' }}
                        </td>
                        <td class="p-4 text-xs text-slate-500">
                            {{ $transfer->creator?->name }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-500">هیچ سابقه انتقالی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $transfers->links() }}
    </div>
</div>
@endsection

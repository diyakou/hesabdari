@extends('layouts.app')

@section('title', 'خدمات فنی و نرم‌افزار')
@section('page-heading', 'خدمات فنی و نرم‌افزار')
@section('page-description', 'پذیرش دستگاه‌های تعمیری، خدمات نرم‌افزاری، انتقال اطلاعات، ساخت اپل آیدی و پیگیری وضعیت')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('services.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="شماره پیگیری یا نام مشتری..."
                class="input-text max-w-xs font-mono"
            >
            <select name="status" class="input-text max-w-xs">
                <option value="">همه وضعیت‌ها</option>
                <option value="queued" @selected(request('status') === 'queued')>در صف انتظار</option>
                <option value="in_progress" @selected(request('status') === 'in_progress')>در حال انجام</option>
                <option value="ready" @selected(request('status') === 'ready')>آماده تحویل</option>
                <option value="delivered" @selected(request('status') === 'delivered')>تحویل به مشتری</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>لغو شده</option>
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('services.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        <div class="flex gap-2">@can('manage-settings')<a href="{{ route('service-definitions.index') }}" class="button-secondary">مدیریت خدمات</a>@endcan<a href="{{ route('services.create') }}" class="button-primary">+ پذیرش خدمت جدید</a></div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">شماره پیگیری</th>
                    <th class="p-4">مشتری</th>
                    <th class="p-4">نوع خدمت</th>
                    <th class="p-4">تکنسین</th>
                    <th class="p-4">تاریخ تحویل موعود</th>
                    <th class="p-4">هزینه مستقیم (تومان)</th>
                    <th class="p-4">وضعیت</th>
                    <th class="p-4">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono font-bold text-slate-900" dir="ltr">
                            <a href="{{ route('services.show', $order) }}" class="text-teal-700 hover:underline">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="p-4 font-semibold text-slate-800">
                            {{ $order->party?->name }}
                        </td>
                        <td class="p-4 text-slate-700">
                            {{ $order->serviceDefinition?->name }}
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $order->technician?->name ?: 'تعیین‌نشده' }}
                        </td>
                        <td class="p-4 text-xs text-slate-600 font-mono" dir="ltr">
                            {{ $order->promised_date ? $order->promised_date->format('Y-m-d') : '—' }}
                        </td>
                        <td class="p-4 text-xs font-mono">
                            @can('canSeeFinancials', App\Models\User::class)
                                {{ $order->direct_cost_rials ? number_format($order->direct_cost_rials / 10) : '۰' }}
                            @else
                                <span class="text-slate-400">محرمانه</span>
                            @endcan
                        </td>
                        <td class="p-4">
                            @php
                                $badgeClasses = match($order->status) {
                                    'queued' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'ready' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'delivered' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span class="rounded-md px-2 py-0.5 text-xs font-medium border {{ $badgeClasses }}">
                                {{ $order->statusLabel() }}
                            </span>
                        </td>
                        <td class="p-4">
                            <a href="{{ route('services.show', $order) }}" class="text-xs font-medium text-slate-600 hover:text-teal-700">مشاهده و مدیریت</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-500">هیچ سفارش خدمتی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $orders->links() }}
    </div>
</div>
@endsection

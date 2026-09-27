@extends('layouts.app')

@section('title', 'دستگاه‌های فیزیکی و IMEI')
@section('page-heading', 'گوشی‌ها و دستگاه‌های فیزیکی')
@section('page-description', 'ردیابی گوشی‌ها بر اساس IMEI، سلامت باتری، وضعیت رجیستری و انبار')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('devices.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="جستجو با شناسه IMEI، سریال یا مدل..."
                class="input-text max-w-xs font-mono"
            >
            <select name="status" class="input-text max-w-xs">
                <option value="">همه وضعیت‌ها</option>
                <option value="available" @selected(request('status') === 'available')>موجود در انبار</option>
                <option value="sold" @selected(request('status') === 'sold')>فروخته‌شده</option>
                <option value="quarantine" @selected(request('status') === 'quarantine')>قرنطینه / بررسی</option>
                <option value="pending_receipt" @selected(request('status') === 'pending_receipt')>در انتظار ورود</option>
                <option value="returned_to_supplier" @selected(request('status') === 'returned_to_supplier')>مرجوع به تأمین‌کننده</option>
            </select>
            <select name="ownership" class="input-text max-w-xs">
                <option value="">همه مالکیت‌ها</option>
                <option value="shop" @selected(request('ownership') === 'shop')>متعلق به فروشگاه</option>
                <option value="customer" @selected(request('ownership') === 'customer')>متعلق به مشتری (خدماتی)</option>
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->hasAny(['search', 'status', 'ownership']))
                <a href="{{ route('devices.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        @can('create', App\Models\Device::class)
            <a href="{{ route('devices.create') }}" class="button-primary">
                + ثبت دستگاه جدید (IMEI)
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">مدل و تنوع</th>
                    <th class="p-4">شناسه IMEI اول</th>
                    <th class="p-4">سلامت باتری</th>
                    <th class="p-4">رجیستری</th>
                    <th class="p-4">وضعیت فیزیکی</th>
                    <th class="p-4">مالکیت</th>
                    <th class="p-4">وضعیت عملیاتی</th>
                    <th class="p-4">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($devices as $device)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-semibold text-slate-900">
                            <a href="{{ route('devices.show', $device) }}" class="text-teal-700 hover:underline">
                                {{ $device->variant?->display_name }}
                            </a>
                        </td>
                        <td class="p-4 font-mono text-xs font-semibold" dir="ltr">
                            {{ $device->primary_imei ?: '—' }}
                        </td>
                        <td class="p-4 text-xs font-mono">
                            @if($device->battery_health !== null)
                                <span @class([
                                    'font-bold',
                                    'text-emerald-600' => $device->battery_health >= 80,
                                    'text-amber-600' => $device->battery_health >= 60 && $device->battery_health < 80,
                                    'text-rose-600' => $device->battery_health < 60,
                                ])>
                                    {{ $device->battery_health }}%
                                </span>
                            @else
                                <span class="text-slate-400">نامشخص</span>
                            @endif
                        </td>
                        <td class="p-4 text-xs">
                            <span class="rounded px-1.5 py-0.5 text-xs font-medium {{ $device->registry_status->value === 'registered' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                {{ $device->registry_status->label() }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $device->physical_condition->label() }}
                        </td>
                        <td class="p-4 text-xs">
                            @if($device->ownership->value === 'shop')
                                <span class="text-slate-700">فروشگاه</span>
                            @else
                                <span class="font-semibold text-amber-700">مشتری (خدماتی)</span>
                            @endif
                        </td>
                        <td class="p-4 text-xs">
                            @php
                                $statusClasses = match($device->operational_status->value) {
                                    'available' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'sold' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'quarantine' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'pending_receipt' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'returned_to_supplier' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <span class="rounded-md border px-2 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                {{ $device->operational_status->label() }}
                            </span>
                        </td>
                        <td class="p-4">
                            <a href="{{ route('devices.show', $device) }}" class="text-xs font-medium text-slate-600 hover:text-teal-700">جزئیات</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-500">هیچ دستگاهی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $devices->links() }}
    </div>
</div>
@endsection

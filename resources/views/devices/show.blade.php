@extends('layouts.app')

@section('title', 'مشخصات دستگاه')
@section('page-heading', 'شناسنامه گوشی: ' . ($device->variant?->display_name ?? 'دستگاه'))

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 border border-teal-200">
                {{ $device->operational_status->label() }}
            </span>
            <span class="text-xs text-slate-500">| {{ $device->ownership->label() }}</span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('devices.index') }}" class="button-secondary">بازگشت به فهرست دستگاه‌ها</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">مشخصات و شناسه‌ها</h3>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between">
                    <span class="text-slate-500">مدل:</span>
                    <span class="font-semibold text-slate-900">{{ $device->variant?->product?->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">رنگ و مشخصات:</span>
                    <span>{{ $device->variant?->color ?: '—' }} ({{ $device->variant?->storage ?: '—' }})</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">IMEI اول (اصلی):</span>
                    <span class="font-mono font-bold text-slate-900" dir="ltr">{{ $device->primary_imei ?: '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">IMEI دوم:</span>
                    <span class="font-mono font-bold text-slate-900" dir="ltr">{{ $device->secondary_imei ?: '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">انبار:</span>
                    <span>{{ $device->warehouse?->name ?: 'نامشخص' }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">ارزیابی فنی و سلامت</h3>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between">
                    <span class="text-slate-500">وضعیت فیزیکی:</span>
                    <span class="font-semibold">{{ $device->physical_condition->label() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">سلامت باتری:</span>
                    <span class="font-mono font-bold text-emerald-600">
                        {{ $device->battery_health !== null ? $device->battery_health . '%' : 'نامشخص' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">وضعیت رجیستری:</span>
                    <span>{{ $device->registry_status->label() }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">یادداشت کارشناسی</h3>
            <p class="text-xs text-slate-700 leading-relaxed">{{ $device->notes ?: 'هیچ یادداشتی ثبت نشده است.' }}</p>
        </div>
    </div>
</div>
@endsection

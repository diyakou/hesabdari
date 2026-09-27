@extends('layouts.app')

@section('title', 'پرونده: ' . $party->name)
@section('page-heading', $party->name)
@section('page-description', 'اطلاعات تماس، سقف اعتبار و سوابق مالی طرف‌حساب')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            @foreach($party->roles as $role)
                <span class="rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 border border-teal-200">
                    {{ $role->role->label() }}
                </span>
            @endforeach
            <span class="text-xs text-slate-500">| {{ $party->type->label() }}</span>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $party)
                <a href="{{ route('parties.edit', $party) }}" class="button-secondary">ویرایش مشخصات</a>
            @endcan
            <a href="{{ route('parties.index') }}" class="button-secondary">بازگشت به فهرست</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">اطلاعات تماس</h3>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between">
                    <span class="text-slate-500">شماره موبایل:</span>
                    <span class="font-mono" dir="ltr">{{ $party->mobile ?: 'ثبت نشده' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">تلفن ثابت:</span>
                    <span class="font-mono" dir="ltr">{{ $party->phone ?: 'ثبت نشده' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">کد ملی / شناسه:</span>
                    <span class="font-mono" dir="ltr">{{ $party->national_id ?: 'ثبت نشده' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block mb-1">آدرس:</span>
                    <p class="text-slate-800">{{ $party->address ?: 'ثبت نشده' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">وضعیت مالی</h3>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between">
                    <span class="text-slate-500">سقف اعتبار:</span>
                    <span class="font-semibold text-slate-900">
                        {{ $party->credit_limit_rials ? number_format($party->credit_limit_rials / 10) . ' تومان' : 'نامحدود' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">وضعیت حساب:</span>
                    <span class="font-semibold text-emerald-600">تسویه / عادی</span>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">یادداشت داخلی</h3>
            <p class="text-xs text-slate-700 leading-relaxed">{{ $party->notes ?: 'هیچ یادداشتی ثبت نشده است.' }}</p>
        </div>
    </div>
</div>
@endsection

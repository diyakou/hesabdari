@extends('layouts.app')

@section('title', 'ایجاد کاربر')
@section('page-heading', 'ایجاد کاربر')
@section('page-description', 'تعریف حساب داخلی و تعیین سطح دسترسی')

@section('content')
    <div class="card mx-auto max-w-4xl p-5 sm:p-7">
        <div class="mb-6 border-b border-slate-200 pb-5">
            <h2 class="text-lg font-black text-slate-950">اطلاعات حساب جدید</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">این حساب فقط برای کارکنان مجاز فروشگاه ایجاد می‌شود.</p>
        </div>

        @include('admin.users._form')
    </div>
@endsection

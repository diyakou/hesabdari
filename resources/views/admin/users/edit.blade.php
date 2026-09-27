@extends('layouts.app')

@section('title', 'ویرایش کاربر')
@section('page-heading', 'ویرایش کاربر')
@section('page-description', 'تغییر مشخصات، نقش یا وضعیت حساب')

@section('content')
    <div class="card mx-auto max-w-4xl p-5 sm:p-7">
        <div class="mb-6 border-b border-slate-200 pb-5">
            <h2 class="text-lg font-black text-slate-950">{{ $editedUser->name }}</h2>
            <p class="mt-1 text-sm text-slate-500" dir="ltr">{{ $editedUser->email }}</p>
        </div>

        @include('admin.users._form')
    </div>
@endsection

@extends('layouts.guest')

@section('title', 'ورود')

@section('content')
    <section class="rounded-3xl border border-white/10 bg-white p-6 shadow-2xl sm:p-8" aria-labelledby="login-title">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 grid size-14 place-items-center rounded-2xl bg-teal-700 text-white shadow-lg shadow-teal-900/20" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="7" y="2.75" width="10" height="18.5" rx="2" />
                    <path d="M10 5.5h4M11 18.5h2" />
                </svg>
            </div>
            <h1 id="login-title" class="text-2xl font-black text-slate-950">ورود به سامانه</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">برای دسترسی به مدیریت فروشگاه، اطلاعات حساب خود را وارد کنید.</p>
        </div>

        <x-status-message />
        <x-validation-summary />

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="form-label">ایمیل</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="form-input text-left"
                    dir="ltr"
                    autocomplete="username"
                    inputmode="email"
                    required
                    autofocus
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                >
                <x-field-error name="email" />
            </div>

            <div>
                <label for="password" class="form-label">رمز عبور</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="form-input text-left"
                    dir="ltr"
                    autocomplete="current-password"
                    required
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                >
                <x-field-error name="password" />
            </div>

            <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl text-sm text-slate-700">
                <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                ورود من را به خاطر بسپار
            </label>

            <button type="submit" class="button-primary w-full">ورود</button>
        </form>

        <p class="mt-6 text-center text-xs leading-5 text-slate-500">ایجاد حساب عمومی غیرفعال است. برای دسترسی با مدیر سامانه تماس بگیرید.</p>
    </section>
@endsection

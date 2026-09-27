@extends('layouts.app')

@section('title', 'پیشخوان')
@section('page-heading', 'داشبورد')
@section('page-description', 'نمای کلی فروشگاه')

@section('content')
<div class="space-y-4">
    <section class="relative overflow-hidden rounded-2xl bg-[#082748] px-6 py-6 text-white shadow-[0_14px_38px_rgba(8,39,72,.15)] sm:px-8">
        <div class="pointer-events-none absolute inset-0 opacity-[.08]" style="background-image:repeating-linear-gradient(115deg,transparent 0,transparent 72px,#60a5fa 73px,#60a5fa 74px)"></div>
        <div class="relative grid items-stretch gap-6 lg:grid-cols-[1fr_1.05fr]">
            <div class="flex min-h-40 flex-col justify-between py-1">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-xl border border-blue-300/40 bg-blue-500 text-xl font-black">R</span>
                    <div><p class="font-black">{{ $store?->name ?? 'RAVINU' }}</p><p class="text-[11px] text-blue-200">مرکز فرمان فروشگاه</p></div>
                </div>
                <div>
                    <p class="mb-2 text-sm font-bold text-sky-300">سلام {{ auth()->user()->name }}</p>
                    <h2 class="text-2xl font-black tracking-tight sm:text-3xl">امروز همه‌چیز تحت کنترل است.</h2>
                    <p class="mt-2 text-xs text-blue-200">فروش، خدمات و پیگیری‌های مهم در یک نگاه</p>
                </div>
            </div>
            <div class="rounded-2xl border border-blue-300/20 bg-white/[.07] p-5 backdrop-blur-sm">
                <div class="flex items-center justify-between text-xs"><span class="rounded-lg bg-sky-400/15 px-2 py-1 text-sky-200">امروز</span><span class="font-bold text-blue-100">{{ now()->format('Y/m/d') }}</span></div>
                <p class="mt-5 text-xs text-blue-200">فروش امروز</p>
                @can('canSeeFinancials', App\Models\User::class)
                    <p class="mt-1 text-3xl font-black font-tabular">{{ number_format($todaySalesTotalRials / 10) }} <span class="text-xs font-normal text-sky-300">تومان</span></p>
                @else
                    <p class="mt-1 text-2xl font-black">{{ number_format($todaySalesCount) }} فاکتور</p>
                @endcan
                <div class="mt-5 grid grid-cols-2 border-t border-blue-200/20 pt-4 text-xs">
                    <div><strong class="block text-lg">{{ number_format($todaySalesCount) }}</strong><span class="text-blue-200">فاکتور فروش</span></div>
                    <div class="border-r border-blue-200/20 pr-5"><strong class="block text-lg">{{ number_format($activeServicesCount) }}</strong><span class="text-blue-200">کار باز خدمات</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="card p-4" aria-labelledby="fast-access-title">
        <div class="mb-3 flex items-center justify-between"><div><p class="text-[10px] font-bold text-blue-600">عملیات روزانه</p><h2 id="fast-access-title" class="text-base font-black">دسترسی سریع</h2></div><span class="text-[11px] text-slate-400">پرتکرارترین کارها</span></div>
        <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-6">
            <a href="{{ route('sales.create') }}" class="quick-tile"><span class="quick-icon">▱</span><span><b>فروش جدید</b><small>فاکتور و پرداخت</small></span></a>
            <a href="{{ route('services.create') }}" class="quick-tile quick-tile-active"><span class="quick-icon">⌘</span><span><b>پذیرش خدمت</b><small>نرم‌افزار و تعمیر</small></span></a>
            <a href="{{ route('purchases.create') }}" class="quick-tile"><span class="quick-icon">⌁</span><span><b>خرید جدید</b><small>ورود به انبار</small></span></a>
            <a href="{{ route('payments.create') }}" class="quick-tile"><span class="quick-icon">↕</span><span><b>دریافت و پرداخت</b><small>اسناد مالی</small></span></a>
            <a href="{{ route('parties.create') }}" class="quick-tile"><span class="quick-icon">♙</span><span><b>مشتری جدید</b><small>دفتر اشخاص</small></span></a>
            <a href="{{ route('products.create') }}" class="quick-tile"><span class="quick-icon">◇</span><span><b>کالای جدید</b><small>موجودی و قیمت</small></span></a>
        </div>
    </section>
    <!-- Primary Operational Metric Cards -->
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Sales Today -->
        <article class="stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">فروش نهایی امروز</p>
                    <p class="text-3xl font-black text-slate-950 font-mono tracking-tight">
                        {{ number_format($todaySalesCount) }}
                        <span class="text-xs font-medium text-slate-400">سند</span>
                    </p>
                    @can('canSeeFinancials', App\Models\User::class)
                        <p class="text-xs font-mono font-bold text-teal-700 pt-1">
                            {{ number_format($todaySalesTotalRials / 10) }} <span class="text-[10px] text-slate-500 font-normal">تومان</span>
                        </p>
                    @endcan
                </div>
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-teal-50 text-teal-700 ring-1 ring-inset ring-teal-500/20 group-hover:scale-105 transition-transform" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-center gap-1.5 text-[11px] text-slate-500 border-t border-slate-100 pt-3">
                <span class="size-1.5 rounded-full bg-teal-500"></span>
                <span>فاکتورهای قطعی‌شده امروز</span>
            </div>
        </article>

        <!-- Active Services -->
        <article class="stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">خدمات در جریان</p>
                    <p class="text-3xl font-black text-slate-950 font-mono tracking-tight">
                        {{ number_format($activeServicesCount) }}
                        <span class="text-xs font-medium text-slate-400">سفارش</span>
                    </p>
                    <p class="text-xs text-slate-500 pt-1">در صف انتظار و کارگاه</p>
                </div>
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-500/20 group-hover:scale-105 transition-transform" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-center gap-1.5 text-[11px] text-blue-700 border-t border-slate-100 pt-3">
                <a href="{{ route('services.index') }}" class="hover:underline flex items-center gap-1">
                    مشاهده صف تعمیرات و نرم‌افزار ←
                </a>
            </div>
        </article>

        <!-- Available Devices -->
        <article class="stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">گوشی‌های موجود انبار</p>
                    <p class="text-3xl font-black text-emerald-700 font-mono tracking-tight">
                        {{ number_format($availableDevicesCount) }}
                        <span class="text-xs font-medium text-slate-400">دستگاه</span>
                    </p>
                    @if($quarantineDevicesCount > 0)
                        <p class="text-xs text-amber-600 font-semibold pt-1">+ {{ $quarantineDevicesCount }} دستگاه در قرنطینه</p>
                    @else
                        <p class="text-xs text-slate-400 pt-1">آماده فروش و تحویل</p>
                    @endif
                </div>
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-500/20 group-hover:scale-105 transition-transform" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="5" y="2" width="14" height="20" rx="2" />
                        <line x1="12" y1="18" x2="12.01" y2="18" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-center gap-1.5 text-[11px] text-slate-500 border-t border-slate-100 pt-3">
                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                <span>ردیابی قطعی با شناسه IMEI</span>
            </div>
        </article>

        <!-- Cash & Bank Balance -->
        <article class="stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">موجودی نقد و بانک</p>
                    @can('canSeeFinancials', App\Models\User::class)
                        <p class="text-3xl font-black text-slate-900 font-mono tracking-tight">
                            {{ number_format($cashAndBankBalanceRials / 10) }}
                        </p>
                        <p class="text-xs text-slate-500 pt-1">تومان (صندوق، بانک، پوز)</p>
                    @else
                        <p class="text-xl font-bold text-slate-400 py-1">محرمانه</p>
                        <p class="text-xs text-slate-400">محدود به مدیر و حسابدار</p>
                    @endcan
                </div>
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-500/20 group-hover:scale-105 transition-transform" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="2" y="5" width="20" height="14" rx="2" />
                        <line x1="2" y1="10" x2="22" y2="10" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-center gap-1.5 text-[11px] text-slate-500 border-t border-slate-100 pt-3">
                <span class="size-1.5 rounded-full bg-purple-500"></span>
                <span>حساب ۱۰۱ دفتر کل</span>
            </div>
        </article>
    </div>

    <!-- Quick Shortcuts & System Overview -->
    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <!-- Quick Action Grid -->
        <section class="card p-6" aria-labelledby="quick-actions-title">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                <div>
                    <h2 id="quick-actions-title" class="text-base font-extrabold text-slate-900">عملیات سریع فروشگاه</h2>
                    <p class="text-xs text-slate-500 mt-0.5">میانبرهای پرکاربرد ثبت فاکتور، خدمات و دستگاه‌ها</p>
                </div>
                <span class="text-xs font-semibold text-teal-700 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-200/60">میز کار فعال</span>
            </div>

            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-4">
                <a href="{{ route('sales.create') }}" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-200/80 hover:border-teal-500/80 hover:bg-teal-50/40 hover:-translate-y-0.5 hover:shadow-sm transition-all text-center group">
                    <span class="size-11 rounded-xl bg-gradient-to-tr from-teal-600 to-emerald-500 text-white flex items-center justify-center mb-2.5 shadow-sm shadow-teal-700/20 group-hover:scale-105 transition-transform">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                    <span class="text-xs font-bold text-slate-800">فاکتور فروش جدید</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">صندوق و POS</span>
                </a>

                <a href="{{ route('services.create') }}" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-200/80 hover:border-blue-500/80 hover:bg-blue-50/40 hover:-translate-y-0.5 hover:shadow-sm transition-all text-center group">
                    <span class="size-11 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center mb-2.5 shadow-sm shadow-blue-700/20 group-hover:scale-105 transition-transform">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                        </svg>
                    </span>
                    <span class="text-xs font-bold text-slate-800">پذیرش خدمت جدید</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">انتقال دیتا / تعمیر</span>
                </a>

                <a href="{{ route('devices.create') }}" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-200/80 hover:border-emerald-500/80 hover:bg-emerald-50/40 hover:-translate-y-0.5 hover:shadow-sm transition-all text-center group">
                    <span class="size-11 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center mb-2.5 shadow-sm shadow-emerald-700/20 group-hover:scale-105 transition-transform">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" />
                            <line x1="12" y1="18" x2="12.01" y2="18" />
                        </svg>
                    </span>
                    <span class="text-xs font-bold text-slate-800">ثبت دستگاه (IMEI)</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">ورود به انبار</span>
                </a>

                @can('canSeeFinancials', App\Models\User::class)
                    <a href="{{ route('reports.index') }}" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-200/80 hover:border-purple-500/80 hover:bg-purple-50/40 hover:-translate-y-0.5 hover:shadow-sm transition-all text-center group">
                        <span class="size-11 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-500 text-white flex items-center justify-center mb-2.5 shadow-sm shadow-purple-700/20 group-hover:scale-105 transition-transform">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 20V10M12 20V4M6 20v-6" />
                            </svg>
                        </span>
                        <span class="text-xs font-bold text-slate-800">گزارش‌های سود</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">تحلیل عملکرد</span>
                    </a>
                @else
                    <a href="{{ route('parties.create') }}" class="flex flex-col items-center justify-center p-4 rounded-2xl border border-slate-200/80 hover:border-slate-400 hover:bg-slate-50/50 hover:-translate-y-0.5 hover:shadow-sm transition-all text-center group">
                        <span class="size-11 rounded-xl bg-gradient-to-tr from-slate-600 to-slate-700 text-white flex items-center justify-center mb-2.5 shadow-sm group-hover:scale-105 transition-transform">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="4" />
                            </svg>
                        </span>
                        <span class="text-xs font-bold text-slate-800">تعریف شخص جدید</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">مشتری / همکار</span>
                    </a>
                @endcan
            </div>

            <!-- Store Details Bar -->
            <dl class="mt-6 divide-y divide-slate-100 rounded-xl bg-slate-50/60 p-4 border border-slate-200/60 text-xs">
                <div class="flex items-center justify-between py-1.5">
                    <dt class="font-medium text-slate-500">نام فروشگاه و سیستم</dt>
                    <dd class="font-bold text-slate-900">{{ $store?->name ?? 'فروشگاه موبایل' }}</dd>
                </div>
                <div class="flex items-center justify-between py-1.5">
                    <dt class="font-medium text-slate-500">منطقه زمانی سرور و سیستم</dt>
                    <dd class="font-mono font-bold text-slate-800" dir="ltr">{{ $store?->timezone ?? 'Asia/Tehran' }}</dd>
                </div>
                <div class="flex items-center justify-between py-1.5">
                    <dt class="font-medium text-slate-500">سیاست ارزی ذخیره و نمایش</dt>
                    <dd class="font-semibold text-slate-800">ذخیره: <strong class="font-bold text-teal-800">ریال (IRR)</strong> | نمایش: <strong class="font-bold text-teal-800">تومان (IRT)</strong></dd>
                </div>
            </dl>
        </section>

        <!-- System Architecture & Status Card -->
        <aside class="card p-6 bg-gradient-to-br from-slate-900 to-slate-800 text-white border-0 shadow-lg space-y-4">
            <div class="flex items-center justify-between border-b border-slate-700/80 pb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-teal-400">وضعیت زیرساخت و هسته مالی</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2 py-0.5 text-[11px] font-semibold text-emerald-300 ring-1 ring-emerald-400/30">
                    <span class="size-1.5 rounded-full bg-emerald-400"></span>
                    فاز اول عملیاتی
                </span>
            </div>

            <p class="text-xs leading-6 text-slate-300">
                سامانه بر مبنای ثبت دوبل خودکار داخلی، میانگین موزون متحرک انبار و اعتبارسنجی سراسری شناسه ۱۵ رقمی IMEI در وضعیت پایدار و فعال قرار دارد.
            </p>

            <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
                <div class="rounded-xl bg-slate-800/80 p-3 border border-slate-700">
                    <span class="text-[11px] text-slate-400 block">کاربران فعال</span>
                    <span class="text-lg font-black text-white font-mono mt-0.5 block">{{ $activeUsersCount }}</span>
                </div>
                <div class="rounded-xl bg-slate-800/80 p-3 border border-slate-700">
                    <span class="text-[11px] text-slate-400 block">شعبه و انبار</span>
                    <span class="text-sm font-bold text-teal-300 mt-1 block">{{ $activeBranchesCount }} شعبه / {{ $activeWarehousesCount }} انبار</span>
                </div>
            </div>

            @can('manage-settings')
                <div class="pt-2">
                    <a href="{{ route('settings.edit') }}" class="inline-flex items-center justify-center w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white border border-slate-700 transition">
                        تنظیمات پیشرفته فروشگاه
                    </a>
                </div>
            @endcan
        </aside>
    </div>
</div>
@endsection

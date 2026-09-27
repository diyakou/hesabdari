<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'پیشخوان') | {{ config('app.name', 'مدیریت فروشگاه موبایل') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f8ff] text-slate-900 antialiased selection:bg-blue-600 selection:text-white">
    <a href="#main-content" class="skip-link">رفتن به محتوای اصلی</a>

    <div class="app-shell min-h-screen lg:grid lg:grid-cols-[11rem_1fr]">
        <!-- Modern Sidebar -->
        <aside id="mobile-navigation" class="app-sidebar fixed inset-y-0 right-0 z-40 hidden w-72 flex-col justify-between border-l border-blue-900 bg-[#082748] text-white shadow-2xl lg:static lg:flex lg:w-auto lg:shadow-none" aria-label="پیمایش اصلی">
            <div>
                <!-- Brand Header -->
                <div class="flex h-18 items-center justify-between border-b border-slate-100 px-5">
                    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-xl transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-tr from-teal-700 to-emerald-600 text-white shadow-sm shadow-teal-900/20" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="7" y="2.75" width="10" height="18.5" rx="2" />
                                <path d="M10 5.5h4M11 18.5h2" />
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-slate-900">سامانه موبایل‌فروشی</span>
                            <span class="flex items-center gap-1.5 text-[11px] text-emerald-700 font-medium">
                                <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                سیستم مالی و انبار
                            </span>
                        </span>
                    </a>
                    <button type="button" class="icon-button lg:hidden" data-navigation-close aria-label="بستن فهرست">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m6 6 12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation Groups -->
                <nav class="space-y-6 p-4 overflow-y-auto max-h-[calc(100vh-10rem)]">
                    <!-- Group 1: Workspace & Operations -->
                    <div>
                        <p class="nav-section-title">میز کار و عملیات</p>
                        <div class="space-y-1">
                            <a href="{{ route('dashboard') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                                <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M4 13h6V4H4v9Zm10 7h6V11h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z" />
                                </svg>
                                پیشخوان اصلی
                            </a>

                            @can('viewAny', App\Models\Invoice::class)
                                <a href="{{ route('sales.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('sales.*')]) @if(request()->routeIs('sales.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    میز فروش / صندوق
                                </a>
                            @endcan

                            @if(!auth()->user()->isWarehouseKeeper())
                                <a href="{{ route('services.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('services.*')]) @if(request()->routeIs('services.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                                    </svg>
                                    خدمات فنی و نرم‌افزار
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Group 2: Catalog & Warehouse -->
                    <div>
                        <p class="nav-section-title">کالاها و انبارداری</p>
                        <div class="space-y-1">
                            @can('viewAny', App\Models\Product::class)
                                <a href="{{ route('products.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('products.*')]) @if(request()->routeIs('products.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    کالاها و تنوع‌ها
                                </a>
                            @endcan

                            @can('viewAny', App\Models\Device::class)
                                <a href="{{ route('devices.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('devices.*')]) @if(request()->routeIs('devices.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="5" y="2" width="14" height="20" rx="2" />
                                        <line x1="12" y1="18" x2="12.01" y2="18" />
                                    </svg>
                                    گوشی‌ها و شناسه‌ها (IMEI)
                                </a>
                            @endcan

                            @can('viewAny', App\Models\Brand::class)
                                <a href="{{ route('brands.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('brands.*')]) @if(request()->routeIs('brands.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z" />
                                    </svg>
                                    برندها
                                </a>
                            @endcan

                            @can('viewAny', App\Models\Category::class)
                                <a href="{{ route('categories.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('categories.*')]) @if(request()->routeIs('categories.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                    </svg>
                                    دسته‌بندی‌ها
                                </a>
                            @endcan

                            @if(!auth()->user()->isSalesperson())
                                <a href="{{ route('inventory.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('inventory.*')]) @if(request()->routeIs('inventory.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                                        <line x1="12" y1="22.08" x2="12" y2="12" />
                                    </svg>
                                    موجودی و انبارگردانی
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Group 3: Financials & Accounting -->
                    <div>
                        <p class="nav-section-title">حسابداری و مالی</p>
                        <div class="space-y-1">
                            @can('viewAny', App\Models\Invoice::class)
                                <a href="{{ route('purchases.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('purchases.*')]) @if(request()->routeIs('purchases.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    فاکتورهای خرید
                                </a>
                            @endcan

                            @can('viewAny', App\Models\Payment::class)
                                <a href="{{ route('payments.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('payments.*')]) @if(request()->routeIs('payments.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                        <line x1="2" y1="10" x2="22" y2="10" />
                                    </svg>
                                    دریافت و پرداخت وجوه
                                </a>
                            @endcan

                            @can('viewAny', App\Models\Party::class)
                                <a href="{{ route('parties.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('parties.*')]) @if(request()->routeIs('parties.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    طرف‌های حساب (اشخاص)
                                </a>
                            @endcan

                            @if(auth()->user()->canSeeFinancials())
                                <a href="{{ route('expenses.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('expenses.*')]) @if(request()->routeIs('expenses.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                    هزینه‌های جاری
                                </a>

                                <a href="{{ route('transfers.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('transfers.*')]) @if(request()->routeIs('transfers.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M17 1l4 4-4 4" />
                                        <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                                        <path d="M7 23l-4-4 4-4" />
                                        <path d="M21 13v2a4 4 0 0 1-4 4H3" />
                                    </svg>
                                    انتقال وجه بین‌حساب‌ها
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Group 4: Reports & System -->
                    <div>
                        <p class="nav-section-title">مدیریت و گزارش‌ها</p>
                        <div class="space-y-1">
                            @if(auth()->user()->canSeeFinancials())
                                <a href="{{ route('reports.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('reports.*')]) @if(request()->routeIs('reports.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M18 20V10M12 20V4M6 20v-6" />
                                    </svg>
                                    گزارش‌های مدیریتی
                                </a>
                            @endif

                            @can('viewAny', App\Models\User::class)
                                <a href="{{ route('admin.users.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.users.*')]) @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M16 20v-1.6a3.4 3.4 0 0 0-3.4-3.4H7.4A3.4 3.4 0 0 0 4 18.4V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7-1 1.2 1.2L21 8.4" />
                                    </svg>
                                    کاربران و دسترسی‌ها
                                </a>
                            @endcan

                            @can('manage-settings')
                                <a href="{{ route('settings.edit') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('settings.*')]) @if(request()->routeIs('settings.*')) aria-current="page" @endif>
                                    <svg viewBox="0 0 24 24" class="size-4.5" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" />
                                        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.86 2.86-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21H9.55v-.1A1.7 1.7 0 0 0 8.5 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.86-2.86.06-.06A1.7 1.7 0 0 0 4.1 15a1.7 1.7 0 0 0-1.5-1H2.5V10h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.34-1.88L3.7 7.06 6.56 4.2l.06.06A1.7 1.7 0 0 0 8.5 4.6a1.7 1.7 0 0 0 1-1.5V3h4.05v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.86 2.86-.06.06A1.7 1.7 0 0 0 18.95 9a1.7 1.7 0 0 0 1.5 1h.1v4h-.1a1.7 1.7 0 0 0-1.05 1Z" />
                                    </svg>
                                    تنظیمات فروشگاه
                                </a>
                            @endcan
                        </div>
                    </div>
                </nav>
            </div>

            <!-- User Profile Bottom Widget -->
            <div class="border-t border-slate-100 bg-slate-50/50 p-3">
                <div class="flex items-center justify-between gap-2 rounded-xl p-2 bg-white border border-slate-200/70 shadow-xs">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-teal-100 text-xs font-bold text-teal-800">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-900">{{ auth()->user()->name }}</p>
                            <span class="inline-block rounded-md bg-slate-100 px-1.5 py-0.2 text-[10px] font-medium text-slate-600">
                                {{ auth()->user()->role->label() }}
                            </span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="grid size-8 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="خروج از حساب">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Mobile backdrop -->
        <div id="navigation-backdrop" class="fixed inset-0 z-30 hidden bg-slate-950/40 backdrop-blur-xs lg:hidden" data-navigation-close aria-hidden="true"></div>

        <!-- Main Body Area -->
        <div class="min-w-0 flex flex-col">
            <!-- Glassmorphic Top Header -->
            <header class="app-topbar sticky top-0 z-20 border-b border-blue-100 bg-white/95 backdrop-blur-md">
                <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 min-w-0">
                        <button type="button" class="icon-button lg:hidden" data-navigation-open aria-controls="mobile-navigation" aria-expanded="false" aria-label="باز کردن فهرست">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>
                        <label class="global-search hidden min-w-[20rem] items-center gap-2 rounded-xl border border-blue-100 bg-white px-4 py-2 text-xs text-slate-400 shadow-sm xl:flex">
                            <svg viewBox="0 0 24 24" class="size-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input type="search" class="w-full border-0 bg-transparent p-0 outline-none placeholder:text-slate-400" placeholder="IMEI، مشتری، کالا، فاکتور یا پذیرش..." aria-label="جست‌وجوی سراسری">
                        </label>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 border border-slate-200/60 font-tabular" dir="ltr">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                            {{ persian_date(now()) }}
                        </span>

                        <span class="hidden items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50/50 px-3 py-1.5 text-[11px] text-slate-500 md:inline-flex"><span class="size-2 rounded-full bg-amber-400"></span> نسخه عملیاتی</span>
                        <span class="hidden rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 sm:inline-flex">ورود امن مدیر</span>
                    </div>
                </div>
            </header>

            <main id="main-content" class="mx-auto w-full max-w-[1440px] flex-1 p-4 pb-28 sm:p-5 sm:pb-28 lg:p-6" tabindex="-1">
                <x-status-message />
                <x-validation-summary />
                @yield('content')
            </main>
        </div>
    </div>

    <nav class="mobile-tabbar lg:hidden" aria-label="دسترسی سریع موبایل">
        <a href="{{ route('dashboard') }}" @class(['mobile-tab', 'is-active' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"/></svg>
            <span>خانه</span>
        </a>
        @can('viewAny', App\Models\Invoice::class)
            <a href="{{ route('sales.index') }}" @class(['mobile-tab', 'is-active' => request()->routeIs('sales.*')]) @if(request()->routeIs('sales.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg>
                <span>فروش</span>
            </a>
        @endcan
        @if(!auth()->user()->isWarehouseKeeper())
            <a href="{{ route('services.index') }}" @class(['mobile-tab', 'mobile-tab-primary', 'is-active' => request()->routeIs('services.*')]) @if(request()->routeIs('services.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.7 6.3a5 5 0 0 1-6 6l-5.4 5.4a2.1 2.1 0 0 0 3 3l5.4-5.4a5 5 0 0 0 6-6l-2.4 2.4-3-3 2.4-2.4Z"/></svg>
                <span>خدمات</span>
            </a>
        @endif
        @can('viewAny', App\Models\Invoice::class)
            <a href="{{ route('purchases.index') }}" @class(['mobile-tab', 'is-active' => request()->routeIs('purchases.*')]) @if(request()->routeIs('purchases.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h2l2 10h10l3-7H6M9 20h.01M17 20h.01"/></svg>
                <span>خرید</span>
            </a>
        @endcan
        @if(auth()->user()->canSeeFinancials())
            <a href="{{ route('payments.index') }}" @class(['mobile-tab', 'is-active' => request()->routeIs('payments.*', 'expenses.*', 'transfers.*', 'reports.*')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg>
                <span>مالی</span>
            </a>
        @else
            <a href="{{ route('devices.index') }}" @class(['mobile-tab', 'is-active' => request()->routeIs('devices.*')])>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
                <span>دستگاه‌ها</span>
            </a>
        @endif
    </nav>

    <div class="select-sheet" data-select-sheet hidden>
        <button type="button" class="select-sheet-backdrop" data-select-sheet-close aria-label="بستن انتخاب‌گر"></button>
        <section class="select-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="select-sheet-title">
            <div class="select-sheet-handle" aria-hidden="true"></div>
            <div class="select-sheet-header">
                <div><span class="select-sheet-kicker">انتخاب کنید</span><h2 id="select-sheet-title">انتخاب گزینه</h2></div>
                <button type="button" class="select-sheet-close" data-select-sheet-close aria-label="بستن">×</button>
            </div>
            <div class="select-sheet-options" data-select-sheet-options></div>
        </section>
    </div>
</body>
</html>

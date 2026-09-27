@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Modern Segmented Reports Navigation Bar -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-2 shadow-xs">
        <nav class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
            <a href="{{ route('reports.index') }}" @class([
                'flex items-center gap-2 rounded-xl px-3.5 py-2 transition-all duration-150',
                'bg-teal-700 text-white shadow-xs font-bold' => request()->routeIs('reports.index'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('reports.index')
            ])>
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                </svg>
                خلاصه فروش و سود
            </a>

            <a href="{{ route('reports.brands') }}" @class([
                'flex items-center gap-2 rounded-xl px-3.5 py-2 transition-all duration-150',
                'bg-teal-700 text-white shadow-xs font-bold' => request()->routeIs('reports.brands'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('reports.brands')
            ])>
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20V10M18 20V4M6 20v-6" />
                </svg>
                عملکرد و سودآوری برندها
            </a>

            <a href="{{ route('reports.customers') }}" @class([
                'flex items-center gap-2 rounded-xl px-3.5 py-2 transition-all duration-150',
                'bg-teal-700 text-white shadow-xs font-bold' => request()->routeIs('reports.customers'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('reports.customers')
            ])>
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                </svg>
                تحلیل و طبقه‌بندی مشتریان
            </a>

            <a href="{{ route('reports.inventory') }}" @class([
                'flex items-center gap-2 rounded-xl px-3.5 py-2 transition-all duration-150',
                'bg-teal-700 text-white shadow-xs font-bold' => request()->routeIs('reports.inventory'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('reports.inventory')
            ])>
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                </svg>
                ارزش موجودی و تطبیق انبار
            </a>

            <a href="{{ route('reports.trial_balance') }}" @class([
                'flex items-center gap-2 rounded-xl px-3.5 py-2 transition-all duration-150',
                'bg-teal-700 text-white shadow-xs font-bold' => request()->routeIs('reports.trial_balance'),
                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('reports.trial_balance')
            ])>
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                تراز آزمایشی و توازن دفتر کل
            </a>
        </nav>
    </div>

    @yield('report_content')
</div>
@endsection

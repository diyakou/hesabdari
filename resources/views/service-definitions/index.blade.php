@extends('layouts.app')
@section('title','مدیریت خدمات')
@section('page-heading','مدیریت خدمات قابل ارائه')
@section('page-description','تعریف، ویرایش و غیرفعال‌سازی خدمات نرم‌افزاری و سخت‌افزاری')
@section('content')
<div class="space-y-4">
    <div class="flex justify-end"><a href="{{ route('service-definitions.create') }}" class="button-primary">+ تعریف خدمت جدید</a></div>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach($definitions as $definition)
        <article class="card p-4 {{ $definition->is_active ? '' : 'opacity-60' }}">
            <div class="flex justify-between gap-3"><div><span class="text-[10px] font-bold text-blue-600">{{ $definition->category === 'hardware' ? 'سخت‌افزاری' : 'نرم‌افزاری' }}</span><h2 class="mt-1 text-sm font-extrabold">{{ $definition->name }}</h2></div><span class="badge {{ $definition->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">{{ $definition->is_active ? 'فعال' : 'غیرفعال' }}</span></div>
            <p class="mt-2 min-h-10 text-xs leading-5 text-slate-500">{{ $definition->description }}</p>
            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs"><span>{{ number_format($definition->default_fee_rials / 10) }} تومان · {{ $definition->orders_count }} سفارش</span><div class="flex gap-2"><a class="font-bold text-blue-700" href="{{ route('service-definitions.edit',$definition) }}">ویرایش</a><form method="POST" action="{{ route('service-definitions.destroy',$definition) }}">@csrf @method('DELETE')<button class="font-bold {{ $definition->is_active ? 'text-rose-600' : 'text-emerald-600' }}">{{ $definition->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}</button></form></div></div>
        </article>
        @endforeach
    </div>
</div>
@endsection

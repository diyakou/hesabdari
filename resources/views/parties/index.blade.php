@extends('layouts.app')

@section('title', 'مدیریت طرف‌های حساب')
@section('page-heading', 'طرف‌های حساب (اشخاص)')
@section('page-description', 'مدیریت اطلاعات مشتریان، تأمین‌کنندگان و سقف اعتبار')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('parties.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="جستجو با نام، موبایل یا کدملی..."
                class="input-text max-w-xs"
            >
            <select name="role" class="input-text max-w-xs">
                <option value="">همه نقش‌ها</option>
                <option value="customer" @selected(request('role') === 'customer')>مشتریان</option>
                <option value="supplier" @selected(request('role') === 'supplier')>تأمین‌کنندگان</option>
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->hasAny(['search', 'role']))
                <a href="{{ route('parties.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        @can('create', App\Models\Party::class)
            <a href="{{ route('parties.create') }}" class="button-primary">
                + ثبت طرف‌حساب جدید
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">نام شخص / شرکت</th>
                    <th class="p-4">نقش‌ها</th>
                    <th class="p-4">شماره موبایل</th>
                    <th class="p-4">سقف اعتبار (تومان)</th>
                    <th class="p-4">وضعیت</th>
                    <th class="p-4">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($parties as $party)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-semibold text-slate-900">
                            <a href="{{ route('parties.show', $party) }}" class="text-teal-700 hover:underline">
                                {{ $party->name }}
                            </a>
                            <span class="block text-xs text-slate-500">{{ $party->type->label() }}</span>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($party->roles as $role)
                                    <span class="rounded-md bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 border border-teal-200">
                                        {{ $role->role->label() }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="p-4 font-mono text-xs" dir="ltr">{{ $party->mobile ?: '—' }}</td>
                        <td class="p-4">
                            {{ $party->credit_limit_rials ? number_format($party->credit_limit_rials / 10) : 'نامحدود' }}
                        </td>
                        <td class="p-4">
                            @if($party->is_active)
                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">فعال</span>
                            @else
                                <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">غیرفعال</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('parties.show', $party) }}" class="text-xs font-medium text-slate-600 hover:text-teal-700">پرونده</a>
                                @can('update', $party)
                                    <a href="{{ route('parties.edit', $party) }}" class="text-xs font-medium text-slate-600 hover:text-teal-700">ویرایش</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-500">هیچ طرف‌حسابی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $parties->links() }}
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'کاربران')
@section('page-heading', 'کاربران و دسترسی‌ها')
@section('page-description', 'مدیریت حساب‌های داخلی و نقش‌های سازمانی')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-600">{{ number_format($users->total()) }} حساب در سامانه ثبت شده است.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="button-primary">
            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            ایجاد کاربر
        </a>
    </div>

    <section class="card overflow-hidden" aria-labelledby="users-list-title">
        <h2 id="users-list-title" class="sr-only">فهرست کاربران</h2>

        @if ($users->isEmpty())
            <div class="px-5 py-16 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-slate-500" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M16 20v-1.6a3.4 3.4 0 0 0-3.4-3.4H7.4A3.4 3.4 0 0 0 4 18.4V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" />
                    </svg>
                </div>
                <p class="mt-4 font-bold text-slate-800">هنوز کاربری ثبت نشده است.</p>
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-right text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold text-slate-600">
                        <tr>
                            <th scope="col" class="px-5 py-3.5">کاربر</th>
                            <th scope="col" class="px-5 py-3.5">نقش</th>
                            <th scope="col" class="px-5 py-3.5">وضعیت</th>
                            <th scope="col" class="px-5 py-3.5">تاریخ ایجاد</th>
                            <th scope="col" class="px-5 py-3.5"><span class="sr-only">عملیات</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $user)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500" dir="ltr">{{ $user->email }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ $user->role->label() }}</td>
                                <td class="px-5 py-4">
                                    @if ($user->is_active)
                                        <span class="badge bg-emerald-100 text-emerald-800">فعال</span>
                                    @else
                                        <span class="badge bg-slate-200 text-slate-700">غیرفعال</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600" dir="ltr">{{ $user->created_at->timezone('Asia/Tehran')->format('Y-m-d H:i') }}</td>
                                <td class="px-5 py-4 text-left">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-bold text-teal-700 hover:bg-teal-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600">ویرایش</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden">
                @foreach ($users as $user)
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-slate-900">{{ $user->name }}</h3>
                                <p class="mt-1 truncate text-xs text-slate-500" dir="ltr">{{ $user->email }}</p>
                            </div>
                            @if ($user->is_active)
                                <span class="badge shrink-0 bg-emerald-100 text-emerald-800">فعال</span>
                            @else
                                <span class="badge shrink-0 bg-slate-200 text-slate-700">غیرفعال</span>
                            @endif
                        </div>
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <span class="text-sm text-slate-600">{{ $user->role->label() }}</span>
                            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-bold text-teal-700 hover:bg-teal-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600">ویرایش</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-4 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </section>
@endsection

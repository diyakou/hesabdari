@extends('layouts.app')

@section('title', 'مدیریت دسته‌بندی‌ها')
@section('page-heading', 'دسته‌بندی‌های کالا')
@section('page-description', 'مدیریت گروه‌ها و دسته‌بندی‌های محصولات (گوشی، لوازم جانبی، قطعات و...)')

@section('content')
<div class="space-y-6" x-data="{ showCreateModal: false }">
    @if($errors->has('category_delete'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            {{ $errors->first('category_delete') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('categories.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="جستجو بر اساس نام یا نامک دسته‌بندی..."
                class="input-text max-w-xs"
            >
            <select name="status" class="input-text max-w-xs">
                <option value="">همه وضعیت‌ها</option>
                <option value="active" @selected(request('status') === 'active')>فقط فعال</option>
                <option value="inactive" @selected(request('status') === 'inactive')>فقط غیرفعال</option>
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('categories.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        @can('create', App\Models\Category::class)
            <button type="button" @click="showCreateModal = true" class="button-primary">
                + ثبت دسته‌بندی جدید
            </button>
        @endcan
    </div>

    <!-- Quick Create Modal -->
    <div
        x-show="showCreateModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4"
        @keydown.escape.window="showCreateModal = false"
    >
        <div
            class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl"
            @click.away="showCreateModal = false"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-800">ثبت دسته‌بندی جدید</h3>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('categories.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="modal_cat_name" class="label">نام دسته‌بندی <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        id="modal_cat_name"
                        name="name"
                        required
                        placeholder="مثلاً: گوشی موبایل، قاب و کاور، باتری"
                        class="input-text"
                        autofocus
                    >
                </div>

                <div>
                    <label for="modal_cat_slug" class="label">نامک انگلیسی / شناسه یکتا (اختیاری)</label>
                    <input
                        type="text"
                        id="modal_cat_slug"
                        name="slug"
                        dir="ltr"
                        placeholder="smartphones (در صورت خالی بودن خودکار تولید می‌شود)"
                        class="input-text font-mono text-sm"
                    >
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input
                        type="checkbox"
                        id="modal_cat_is_active"
                        name="is_active"
                        value="1"
                        checked
                        class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                    >
                    <label for="modal_cat_is_active" class="text-xs font-medium text-slate-700">دسته‌بندی فعال باشد</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="showCreateModal = false" class="button-secondary">انصراف</button>
                    <button type="submit" class="button-primary">ذخیره دسته‌بندی</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">نام دسته‌بندی</th>
                    <th class="p-4">نامک (Slug)</th>
                    <th class="p-4">تعداد کالاها</th>
                    <th class="p-4">وضعیت</th>
                    <th class="p-4">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($categories as $category)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="p-4 font-semibold text-slate-900">
                            {{ $category->name }}
                        </td>
                        <td class="p-4 font-mono text-xs text-slate-500" dir="ltr">
                            {{ $category->slug }}
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                <svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                {{ number_format($category->products_count) }} کالا
                            </span>
                        </td>
                        <td class="p-4">
                            @if($category->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200/60">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                    فعال
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 border border-slate-200">
                                    <span class="size-1.5 rounded-full bg-slate-400"></span>
                                    غیرفعال
                                </span>
                            @endif
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                @can('update', $category)
                                    <a href="{{ route('categories.edit', $category) }}" class="text-xs font-medium text-teal-700 hover:text-teal-800 transition-colors">
                                        ویرایش
                                    </a>
                                @endcan

                                @can('delete', $category)
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700 transition-colors">
                                            حذف
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">
                            هیچ دسته‌بندی ثبت نشده است. برای شروع از دکمه «ثبت دسته‌بندی جدید» استفاده کنید.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $categories->links() }}
    </div>
</div>
@endsection

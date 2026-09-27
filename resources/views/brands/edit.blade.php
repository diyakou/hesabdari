@extends('layouts.app')

@section('title', 'ویرایش برند')
@section('page-heading', 'ویرایش برند: ' . $brand->name)
@section('page-description', 'به‌روزرسانی نام، شناسه یکتا و وضعیت فعال‌بودن برند')

@section('content')
<div class="max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('brands.update', $brand) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="label">نام برند <span class="text-rose-500">*</span></label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $brand->name) }}"
                required
                class="input-text"
                autofocus
            >
            @error('name')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="slug" class="label">نامک (Slug) یکتا <span class="text-rose-500">*</span></label>
            <input
                type="text"
                id="slug"
                name="slug"
                value="{{ old('slug', $brand->slug) }}"
                dir="ltr"
                required
                class="input-text font-mono text-sm"
            >
            <p class="mt-1 text-xs text-slate-500">شناسه متنی برای استفاده در آدرس‌ها و تفکیک سیستمی</p>
            @error('slug')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2 pt-2">
            <input
                type="hidden"
                name="is_active"
                value="0"
            >
            <input
                type="checkbox"
                id="is_active"
                name="is_active"
                value="1"
                @checked(old('is_active', $brand->is_active))
                class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
            >
            <label for="is_active" class="text-sm font-medium text-slate-700">برند فعال باشد (قابل انتخاب در تعریف کالا)</label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('brands.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">ذخیره تغییرات</button>
        </div>
    </form>
</div>
@endsection

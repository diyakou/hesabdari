@extends('layouts.app')
@section('title',$definition->exists ? 'ویرایش خدمت' : 'تعریف خدمت')
@section('page-heading',$definition->exists ? 'ویرایش خدمت' : 'تعریف خدمت جدید')
@section('content')
<form method="POST" action="{{ $definition->exists ? route('service-definitions.update',$definition) : route('service-definitions.store') }}" class="card mx-auto max-w-2xl space-y-5 p-6">@csrf @if($definition->exists) @method('PUT') @endif
<div><label class="form-label">عنوان خدمت *</label><input class="input-text" name="name" required value="{{ old('name',$definition->name) }}"></div>
<div class="grid gap-4 sm:grid-cols-2"><div><label class="form-label">دسته‌بندی *</label><select class="input-text" name="category" required><option value="software" @selected(old('category',$definition->category)==='software')>نرم‌افزاری</option><option value="hardware" @selected(old('category',$definition->category)==='hardware')>سخت‌افزاری</option></select></div><div><label class="form-label">تعرفه پایه (تومان)</label><input class="input-text font-mono" type="number" min="0" name="default_fee_toman" value="{{ old('default_fee_toman',$definition->default_fee_rials ? $definition->default_fee_rials/10 : 0) }}"></div></div>
<div><label class="form-label">شرح خدمت</label><textarea class="input-text" rows="4" name="description">{{ old('description',$definition->description) }}</textarea></div>
<div class="flex justify-end gap-3"><a class="button-secondary" href="{{ route('service-definitions.index') }}">انصراف</a><button class="button-primary">ذخیره خدمت</button></div>
</form>
@endsection

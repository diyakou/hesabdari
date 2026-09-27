@extends('layouts.app')

@section('title', 'ثبت انتقال وجه جدید')
@section('page-heading', 'ثبت انتقال وجه بین حساب‌ها')
@section('page-description', 'انتقال داخلی نقدینگی بدون تغییر در سود و زیان (تنها جابجایی تراز دارایی‌های نقدی)')

@section('content')
<div class="mx-auto max-w-2xl">
    <form method="POST" action="{{ route('transfers.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">مشخصات انتقال نقدینگی</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            حساب مبدأ (کسر وجه) <span class="text-rose-500">*</span>
                        </label>
                        <select name="source_account_id" class="input-text w-full" required>
                            <option value="">-- انتخاب حساب مبدأ --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('source_account_id') == $acc->id)>
                                    {{ $acc->name }} ({{ $acc->type }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            حساب مقصد (واریز وجه) <span class="text-rose-500">*</span>
                        </label>
                        <select name="destination_account_id" class="input-text w-full" required>
                            <option value="">-- انتخاب حساب مقصد --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('destination_account_id') == $acc->id)>
                                    {{ $acc->name }} ({{ $acc->type }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            مبلغ انتقال (تومان) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="amount_toman"
                            value="{{ old('amount_toman') }}"
                            required
                            min="1"
                            placeholder="مثال: ۵۰۰۰۰۰۰"
                            class="input-text w-full font-mono"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            تاریخ انتقال <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="date"
                            name="date"
                            value="{{ old('date', now()->toDateString()) }}"
                            required
                            class="input-text w-full font-mono"
                            dir="ltr"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        شماره ارجاع / پیگیری بانکی
                    </label>
                    <input
                        type="text"
                        name="tracking_number"
                        value="{{ old('tracking_number') }}"
                        placeholder="شماره پیگیری فیش، رسید پایا یا ساتنا..."
                        class="input-text w-full font-mono"
                        dir="ltr"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        یادداشت
                    </label>
                    <textarea
                        name="notes"
                        rows="2"
                        placeholder="توضیحات در خصوص علت یا جزئیات انتقال..."
                        class="input-text w-full"
                    >{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('transfers.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">ثبت انتقال وجه و تراز سند</button>
        </div>
    </form>
</div>
@endsection

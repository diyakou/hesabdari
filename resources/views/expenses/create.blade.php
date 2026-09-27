@extends('layouts.app')

@section('title', 'ثبت هزینه جدید')
@section('page-heading', 'ثبت سند هزینه')
@section('page-description', 'ثبت هزینه‌های جاری کسب‌وکار با کسر از حساب مالی و صدور آرتیکل حسابداری خودکار')

@section('content')
<div class="mx-auto max-w-2xl">
    <form method="POST" action="{{ route('expenses.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">اطلاعات سند هزینه</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        دسته‌بندی هزینه <span class="text-rose-500">*</span>
                    </label>
                    <select name="category" class="input-text w-full" required>
                        <option value="">-- انتخاب دسته‌بندی هزینه --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            حساب پرداخت‌کننده <span class="text-rose-500">*</span>
                        </label>
                        <select name="financial_account_id" class="input-text w-full" required>
                            <option value="">-- انتخاب صندوق / بانک / کارتخوان --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('financial_account_id') == $acc->id)>
                                    {{ $acc->name }} ({{ $acc->type }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            طرف حساب / دریافت‌کننده وجه
                        </label>
                        <select name="party_id" class="input-text w-full">
                            <option value="">-- بدون شخص خاص (عمومی) --</option>
                            @foreach($parties as $p)
                                <option value="{{ $p->id }}" @selected(old('party_id') == $p->id)>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            مبلغ هزینه (تومان) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="amount_toman"
                            value="{{ old('amount_toman') }}"
                            required
                            min="1"
                            placeholder="مثال: ۲۵۰۰۰۰"
                            class="input-text w-full font-mono"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            تاریخ پرداخت / تحقق <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            data-jdp
                            autocomplete="off"
                            name="date"
                            value="{{ persian_date(old('date', now())) }}"
                            required
                            class="input-text w-full font-mono"
                            dir="ltr"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        شرح یا بابت هزینه
                    </label>
                    <textarea
                        name="description"
                        rows="3"
                        placeholder="توضیحات تکمیلی بابت این هزینه..."
                        class="input-text w-full"
                    >{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('expenses.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">ثبت سند هزینه و صدور سند حسابداری</button>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('title', 'هزینه‌های جاری فروشگاه')
@section('page-heading', 'هزینه‌های جاری فروشگاه')
@section('page-description', 'ثبت و پایش هزینه‌های عمومی، اداری، اجاره، قبوض و حقوق با سند حسابداری دوطرفه')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <select name="category" class="input-text max-w-xs">
                <option value="">همه دسته‌بندی‌های هزینه</option>
                <option value="اجاره محل فروشگاه" @selected(request('category') === 'اجاره محل فروشگاه')>اجاره محل فروشگاه</option>
                <option value="حقوق و دستمزد پرسنل" @selected(request('category') === 'حقوق و دستمزد پرسنل')>حقوق و دستمزد پرسنل</option>
                <option value="قبوض آب، برق، گاز و تلفن" @selected(request('category') === 'قبوض آب، برق، گاز و تلفن')>قبوض آب، برق، گاز و تلفن</option>
                <option value="تبلیغات و بازاریابی" @selected(request('category') === 'تبلیغات و بازاریابی')>تبلیغات و بازاریابی</option>
                <option value="بسته‌بندی و ارسال (پیک/پست)" @selected(request('category') === 'بسته‌بندی و ارسال (پیک/پست)')>بسته‌بندی و ارسال (پیک/پست)</option>
                <option value="ملزومات مصرفی و اداری" @selected(request('category') === 'ملزومات مصرفی و اداری')>ملزومات مصرفی و اداری</option>
                <option value="تعمیرات و نگهداری تجهیزات" @selected(request('category') === 'تعمیرات و نگهداری تجهیزات')>تعمیرات و نگهداری تجهیزات</option>
                <option value="سایر هزینه‌های عمومی" @selected(request('category') === 'سایر هزینه‌های عمومی')>سایر هزینه‌های عمومی</option>
            </select>
            <button type="submit" class="button-secondary">فیلتر</button>
            @if(request()->has('category'))
                <a href="{{ route('expenses.index') }}" class="button-secondary">پاک کردن</a>
            @endif
        </form>

        <a href="{{ route('expenses.create') }}" class="button-primary">
            + ثبت هزینه جدید
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-right text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                <tr>
                    <th class="p-4">شماره سند</th>
                    <th class="p-4">دسته‌بندی</th>
                    <th class="p-4">حساب پرداخت‌کننده</th>
                    <th class="p-4">طرف حساب / ذینفع</th>
                    <th class="p-4">تاریخ</th>
                    <th class="p-4">مبلغ (تومان)</th>
                    <th class="p-4">شرح</th>
                    <th class="p-4">ثبت‌کننده</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($expenses as $expense)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono font-bold text-slate-900" dir="ltr">
                            {{ $expense->expense_number }}
                        </td>
                        <td class="p-4 font-semibold text-slate-800">
                            {{ $expense->category }}
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $expense->financialAccount?->name }}
                        </td>
                        <td class="p-4 text-xs text-slate-600">
                            {{ $expense->party?->name ?: '—' }}
                        </td>
                        <td class="p-4 text-xs text-slate-600 font-mono" dir="ltr">
                            {{ persian_date($expense->date) }}
                        </td>
                        <td class="p-4 font-semibold text-rose-700 font-mono">
                            {{ number_format($expense->amount_rials / 10) }}
                        </td>
                        <td class="p-4 text-xs text-slate-500 max-w-xs truncate">
                            {{ $expense->description ?: '—' }}
                        </td>
                        <td class="p-4 text-xs text-slate-500">
                            {{ $expense->creator?->name }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-500">هیچ سند هزینه‌ای ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $expenses->links() }}
    </div>
</div>
@endsection

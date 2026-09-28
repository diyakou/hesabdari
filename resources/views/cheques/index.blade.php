@extends('layouts.app')
@section('title', 'مدیریت چک‌ها')
@section('page-heading', 'چک‌های دریافتی و پرداختی')
@section('page-description', 'وضعیت چک‌های دریافتی، صادرشده و خرج‌شده')
@section('content')
<div class="space-y-5">
 <div class="flex flex-wrap items-center justify-between gap-3">
  <form method="GET" class="flex flex-wrap gap-2"><select name="direction" class="input-text"><option value="">همه چک‌ها</option><option value="received" @selected(request('direction') === 'received')>دریافتی</option><option value="issued" @selected(request('direction') === 'issued')>پرداختی</option></select><select name="status" class="input-text"><option value="">همه وضعیت‌ها</option><option value="on_hand" @selected(request('status') === 'on_hand')>موجود</option><option value="scheduled" @selected(request('status') === 'scheduled')>در انتظار سررسید</option><option value="endorsed" @selected(request('status') === 'endorsed')>خرج‌شده</option></select><button class="button-secondary">فیلتر</button></form>
  <div class="flex gap-2"><a class="button-primary bg-emerald-700" href="{{ route('payments.create', ['type' => 'receipt']) }}">+ دریافت چک</a><a class="button-primary" href="{{ route('payments.create', ['type' => 'payment']) }}">صدور / خرج چک</a></div>
 </div>
 <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm"><table class="w-full text-right text-sm"><thead class="bg-slate-50"><tr><th class="p-4">شماره چک</th><th class="p-4">نوع</th><th class="p-4">طرف حساب</th><th class="p-4">بانک / صاحب حساب</th><th class="p-4">مبلغ (تومان)</th><th class="p-4">سررسید</th><th class="p-4">وضعیت</th></tr></thead><tbody class="divide-y divide-slate-100">
 @forelse($cheques as $cheque)<tr><td class="p-4 font-mono">{{ $cheque->check_number }}</td><td class="p-4">{{ $cheque->direction === 'received' ? 'دریافتی' : 'پرداختی' }}</td><td class="p-4">{{ $cheque->party?->name }}@if($cheque->endorsedToParty)<div class="text-xs text-teal-700">خرج‌شده برای: {{ $cheque->endorsedToParty->name }}</div>@endif</td><td class="p-4">{{ $cheque->bank_name }}<div class="text-xs text-slate-500">{{ $cheque->account_owner }}</div></td><td class="p-4 font-mono font-bold">{{ number_format($cheque->amount_rials / 10) }}</td><td class="p-4 font-mono">{{ persian_date($cheque->due_date) }}</td><td class="p-4">{{ ['on_hand'=>'موجود','scheduled'=>'در انتظار سررسید','endorsed'=>'خرج‌شده','cleared'=>'وصول‌شده','bounced'=>'برگشتی','void'=>'باطل'][$cheque->status] ?? $cheque->status }}</td></tr>
 @empty<tr><td colspan="7" class="p-8 text-center text-slate-500">چکی ثبت نشده است.</td></tr>@endforelse
 </tbody></table></div>{{ $cheques->links() }}
</div>
@endsection

@extends('layouts.app')

@section('title', 'ثبت برگشت از فروش')
@section('page-heading', 'ثبت برگشت از فروش')
@section('page-description', 'برگشت کالای فروخته‌شده یا گوشی موبایل (انتقال به قرنطینه و برگشت بهای تمام‌شده بر مبنای اسنپ‌شات اولیه)')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    @if(! $invoice)
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-800 mb-4">جستجوی فاکتور فروش مرجع</h2>
            <form method="GET" action="{{ route('returns.create') }}" class="flex items-center gap-3">
                <input
                    type="number"
                    name="invoice_id"
                    placeholder="شناسه فاکتور فروش..."
                    required
                    class="input-text max-w-xs font-mono"
                >
                <button type="submit" class="button-primary">بارگذاری فاکتور</button>
            </form>
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-800">فاکتور فروش مرجع</h2>
                    <p class="text-xs text-slate-500 font-mono mt-0.5" dir="ltr">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="text-left">
                    <span class="text-xs text-slate-500 block">مشتری</span>
                    <span class="font-bold text-slate-800">{{ $invoice->party?->name }}</span>
                </div>
            </div>

            <h3 class="text-sm font-bold text-slate-700 mt-4">اقلام فاکتور جهت انتخاب برای مرجوعی:</h3>

            <div class="divide-y divide-slate-100">
                @foreach($invoice->lines as $line)
                    @php
                        $alreadyReturned = (int) \App\Models\InvoiceLine::where('reference_line_id', $line->id)
                            ->whereHas('invoice', fn ($q) => $q->where('type', 'sale_return')->where('status', 'finalized'))
                            ->sum('quantity');
                        $returnable = $line->quantity - $alreadyReturned;
                    @endphp

                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold text-slate-800">
                                {{ $line->variant?->product?->name ?? 'کالا/خدمت' }}
                                @if($line->variant?->variant_name)
                                    <span class="text-xs text-slate-500">({{ $line->variant->variant_name }})</span>
                                @endif
                            </div>
                            @if($line->device)
                                <div class="text-xs font-mono text-slate-600 mt-0.5" dir="ltr">
                                    IMEI: {{ $line->device->primaryIdentifier?->value ?? '—' }}
                                </div>
                            @endif
                            <div class="text-xs text-slate-500 mt-1">
                                تعداد در فاکتور: {{ $line->quantity }} | قبلاً مرجوع‌شده: {{ $alreadyReturned }} | قابل برگشت: <strong class="text-teal-700">{{ $returnable }}</strong>
                            </div>
                        </div>

                        @if($returnable > 0)
                            <form method="POST" action="{{ route('returns.store') }}" class="flex items-center gap-3">
                                @csrf
                                <input type="hidden" name="reference_invoice_id" value="{{ $invoice->id }}">
                                <input type="hidden" name="reference_line_id" value="{{ $line->id }}">

                                <div>
                                    <input
                                        type="number"
                                        name="quantity"
                                        value="{{ $line->device ? 1 : 1 }}"
                                        min="1"
                                        max="{{ $returnable }}"
                                        {{ $line->device ? 'readonly' : '' }}
                                        class="input-text w-20 font-mono text-center"
                                        title="تعداد مرجوعی"
                                    >
                                </div>

                                <input
                                    type="text"
                                    name="notes"
                                    placeholder="علت مرجوعی..."
                                    class="input-text text-xs max-w-xs"
                                >

                                <button type="submit" class="button-primary bg-rose-600 hover:bg-rose-700 shrink-0 text-xs">
                                    ثبت برگشت
                                </button>
                            </form>
                        @else
                            <span class="text-xs font-medium text-slate-400">تماماً مرجوع شده</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

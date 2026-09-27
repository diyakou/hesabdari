@extends('layouts.app')

@section('title', 'صدور فاکتور فروش (POS)')
@section('page-heading', 'میز فروش / صدور فاکتور')
@section('page-description', 'فروش ترکیبی گوشی‌های دارای IMEI، لوازم جانبی و خدمات با تسویه فوری')

@section('content')
<div class="max-w-4xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('sales.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="party_id" class="label">مشتری طرف‌حساب <span class="text-rose-500">*</span></label>
                <select id="party_id" name="party_id" required class="input-text">
                    <option value="">انتخاب مشتری...</option>
                    @foreach($customers as $cust)
                        <option value="{{ $cust->id }}" @selected(old('party_id') == $cust->id)>{{ $cust->name }} ({{ $cust->mobile }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="warehouse_id" class="label">انبار مبدأ <span class="text-rose-500">*</span></label>
                <select id="warehouse_id" name="warehouse_id" required class="input-text">
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="issue_date" class="label">تاریخ فروش <span class="text-rose-500">*</span></label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" required class="input-text font-mono">
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4">
            <h3 class="text-sm font-bold text-slate-800 mb-3">اقلام فاکتور فروش (گوشی با IMEI، لوازم جانبی یا خدمات)</h3>
            <p class="mb-3 text-xs text-slate-500">برای هر ردیف می‌توانید نوع تخفیف را مبلغی (تومان) یا درصدی انتخاب کنید؛ تخفیف کل فاکتور نیز جداگانه محاسبه می‌شود.</p>

            <div class="space-y-4" data-repeatable-lines>
                <div data-repeatable-row class="p-4 rounded-lg bg-slate-50 border border-slate-200 grid grid-cols-1 gap-4 sm:grid-cols-4 items-end">
                    <div class="sm:col-span-4 flex items-center justify-between">
                        <span class="text-xs font-bold text-blue-700" data-line-label>ردیف ۱</span>
                        <button type="button" data-remove-line class="hidden rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50">حذف ردیف</button>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">کالا یا خدمت <span class="text-rose-500">*</span></label>
                        <select name="lines[0][product_variant_id]" required class="input-text">
                            <option value="">انتخاب کالا / تنوع...</option>
                            @foreach($variants as $variant)
                                <option value="{{ $variant->id }}">
                                    {{ $variant->display_name }} ({{ $variant->product->type->label() }}) - قیمت: {{ number_format($variant->selling_price_rials / 10) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">تعداد <span class="text-rose-500">*</span></label>
                        <input type="number" name="lines[0][quantity]" value="1" min="1" required class="input-text font-mono">
                    </div>

                    <div>
                        <label class="label">قیمت واحد فروش (تومان) <span class="text-rose-500">*</span></label>
                        <input type="number" name="lines[0][unit_price_toman]" min="0" required class="input-text font-mono">
                    </div>

                    <div>
                        <label class="label">نوع تخفیف ردیف</label>
                        <select name="lines[0][discount_type]" class="input-text">
                            <option value="amount">مبلغی (تومان)</option>
                            <option value="percentage">درصدی</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">مقدار تخفیف ردیف</label>
                        <input type="number" name="lines[0][discount_value]" value="0" min="0" step="0.01" class="input-text font-mono">
                    </div>

                    <div class="sm:col-span-4">
                        <label class="label text-xs text-teal-700 font-semibold">گوشی مشخص دارای IMEI (برای اقلام سریالی الزامی است):</label>
                        <select name="lines[0][device_id]" class="input-text font-mono">
                            <option value="">در صورت خرید گوشی، دستگاه مشخص را انتخاب کنید...</option>
                            @foreach($availableDevices as $dev)
                                <option value="{{ $dev->id }}">
                                    {{ $dev->variant?->display_name }} - IMEI: {{ $dev->primary_imei }} (سلامت باتری: {{ $dev->battery_health ?: '—' }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="button" data-add-line class="button-secondary w-full border-dashed text-blue-700">+ افزودن سریع کالا یا خدمت</button>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="discount_toman" class="label">تخفیف کل فاکتور (تومان)</label>
                <input type="number" id="discount_toman" name="discount_toman" value="{{ old('discount_toman', 0) }}" min="0" class="input-text font-mono">
                <p class="text-xs text-slate-400 mt-1">تخفیف به صورت خودکار و تناسبی بین ردیف‌های فاکتور توزیع می‌شود.</p>
            </div>

            <div>
                <label for="notes" class="label">یادداشت فاکتور</label>
                <textarea id="notes" name="notes" rows="2" class="input-text">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4 bg-teal-50/50 p-4 rounded-xl border border-teal-100">
            <h3 class="text-sm font-bold text-teal-900 mb-3">تسویه فوری فاکتور (صندوق / کارت‌خوان)</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="payment_amount_toman" class="label">مبلغ دریافتی (تومان)</label>
                    <input type="number" id="payment_amount_toman" name="payment[amount_toman]" value="{{ old('payment.amount_toman') }}" min="0" placeholder="مبلغ تسویه فوری" class="input-text font-mono">
                </div>

                <div>
                    <label for="payment_financial_account_id" class="label">حساب مقصد وجه</label>
                    <select id="payment_financial_account_id" name="payment[financial_account_id]" class="input-text">
                        <option value="">انتخاب حساب...</option>
                        @foreach($financialAccounts as $fa)
                            <option value="{{ $fa->id }}">{{ $fa->name }} ({{ $fa->type }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="payment_method" class="label">روش دریافت</label>
                    <select id="payment_method" name="payment[payment_method]" class="input-text">
                        <option value="cash">نقدی (صندوق)</option>
                        <option value="pos">کارت‌خوان (POS)</option>
                        <option value="bank_transfer">انتقال بانکی</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <div class="flex items-center gap-2">
                <input type="hidden" name="finalize_now" value="0">
                <input type="checkbox" id="finalize_now" name="finalize_now" value="1" checked class="rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                <label for="finalize_now" class="text-sm font-semibold text-slate-800">نهایی‌سازی فوری فاکتور و کسر موجودی از انبار</label>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('sales.index') }}" class="button-secondary">انصراف</a>
                <button type="submit" class="button-primary bg-teal-700 hover:bg-teal-800">ثبت فاکتور فروش</button>
            </div>
        </div>
    </form>
</div>
@endsection

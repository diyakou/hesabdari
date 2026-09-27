@extends('layouts.app')

@section('title', 'ثبت فاکتور خرید')
@section('page-heading', 'ثبت فاکتور خرید جدید')
@section('page-description', 'ورود پیش‌نویس خرید، تعیین سرشکن هزینه‌های جانبی و انتخاب کالاها')

@section('content')
<div class="max-w-4xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('purchases.store') }}" class="space-y-6" x-data="{ paymentType: '{{ old('payment_type', 'cash') }}', checks: {{ Js::from(old('checks', [['check_number'=>'','sayad_id'=>'','bank_name'=>'','account_owner'=>'','amount_toman'=>'','due_date'=>'']])) }} }">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="party_id" class="label">تأمین‌کننده / فروشنده <span class="text-rose-500">*</span></label>
                <select id="party_id" name="party_id" required class="input-text">
                    <option value="">انتخاب تأمین‌کننده...</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('party_id') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="warehouse_id" class="label">انبار مقصد <span class="text-rose-500">*</span></label>
                <select id="warehouse_id" name="warehouse_id" required class="input-text">
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="issue_date" class="label">تاریخ فاکتور <span class="text-rose-500">*</span></label>
                <input type="text" data-jdp autocomplete="off" id="issue_date" name="issue_date" value="{{ persian_date(old('issue_date', now())) }}" required class="input-text font-mono">
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4">
            <h3 class="text-sm font-bold text-slate-800 mb-3">ردیف کالاها و خدمات خرید</h3>

            <div class="space-y-4" data-repeatable-lines>
                <div data-repeatable-row class="p-4 rounded-lg bg-slate-50 border border-slate-200 grid grid-cols-1 gap-4 sm:grid-cols-5 items-end">
                    <div class="sm:col-span-5 flex items-center justify-between">
                        <span class="text-xs font-bold text-blue-700" data-line-label>ردیف ۱</span>
                        <button type="button" data-remove-line class="hidden rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50">حذف ردیف</button>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">کالا / تنوع <span class="text-rose-500">*</span></label>
                        <select name="lines[0][product_variant_id]" required class="input-text">
                            <option value="">انتخاب کالا...</option>
                            @foreach($variants as $variant)
                                <option value="{{ $variant->id }}">
                                    {{ $variant->display_name }} ({{ $variant->product->type->label() }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label">تعداد <span class="text-rose-500">*</span></label>
                        <input type="number" name="lines[0][quantity]" value="1" min="1" required class="input-text font-mono">
                    </div>

                    <div>
                        <label class="label">قیمت واحد (تومان) <span class="text-rose-500">*</span></label>
                        <input type="number" name="lines[0][unit_price_toman]" min="0" required class="input-text font-mono">
                    </div>

                    <div>
                        <label class="label">تخفیف ردیف (تومان)</label>
                        <input type="number" name="lines[0][discount_toman]" value="0" min="0" class="input-text font-mono">
                    </div>

                    <div class="sm:col-span-5">
                        <label class="label text-xs text-slate-500">انتخاب دستگاه آماده ورود (فقط در صورتی که کالا گوشی سریالی است):</label>
                        <select name="lines[0][device_id]" class="input-text">
                            <option value="">انتخاب دستگاه (اختیاری برای کالای غیرسریالی)...</option>
                            @foreach($pendingDevices as $dev)
                                <option value="{{ $dev->id }}">
                                    {{ $dev->variant?->display_name }} - IMEI: {{ $dev->primary_imei }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="button" data-add-line class="button-secondary w-full border-dashed text-blue-700">+ افزودن ردیف خرید</button>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="additional_cost_toman" class="label">هزینه جانبی خرید (تسهیم در بهای تمام‌شده) به تومان</label>
                <input type="number" id="additional_cost_toman" name="additional_cost_toman" value="{{ old('additional_cost_toman', 0) }}" min="0" class="input-text font-mono">
                <p class="text-xs text-slate-400 mt-1">این هزینه به نسبت بهای خالص کالاها سرشکن شده و بخشی از بدهی تأمین‌کننده محسوب می‌شود.</p>
            </div>

            <div>
                <label for="notes" class="label">توضیحات و یادداشت فاکتور</label>
                <textarea id="notes" name="notes" rows="2" class="input-text">{{ old('notes') }}</textarea>
            </div>
        </div>

        <section class="rounded-xl border border-blue-100 bg-blue-50/40 p-4">
            <h3 class="mb-3 text-sm font-extrabold text-slate-800">شرایط پرداخت خرید</h3>
            <div class="mb-4 grid grid-cols-2 gap-2">
                <label class="cursor-pointer rounded-lg border p-3 text-center text-xs font-bold" :class="paymentType === 'cash' ? 'border-blue-600 bg-blue-600 text-white' : 'border-blue-200 bg-white'"><input class="sr-only" type="radio" name="payment_type" value="cash" x-model="paymentType">خرید نقدی</label>
                <label class="cursor-pointer rounded-lg border p-3 text-center text-xs font-bold" :class="paymentType === 'installment' ? 'border-blue-600 bg-blue-600 text-white' : 'border-blue-200 bg-white'"><input class="sr-only" type="radio" name="payment_type" value="installment" x-model="paymentType">خرید اقساطی / چکی</label>
            </div>
            <div x-show="paymentType === 'installment'" x-cloak class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="label">پیش‌پرداخت (تومان)</label><input class="input-text font-mono" type="number" min="0" name="down_payment_toman" value="{{ old('down_payment_toman', 0) }}"></div>
                    <div><label class="label">توضیحات قرارداد اقساط</label><input class="input-text" name="installment_notes" value="{{ old('installment_notes') }}" placeholder="شرایط توافق‌شده با تأمین‌کننده"></div>
                </div>
                <template x-for="(check, index) in checks" :key="index">
                    <div class="rounded-xl border border-blue-100 bg-white p-4">
                        <div class="mb-3 flex justify-between"><strong class="text-xs text-blue-800" x-text="'چک شماره ' + (index + 1)"></strong><button type="button" x-show="checks.length > 1" @click="checks.splice(index, 1)" class="text-xs font-bold text-rose-600">حذف</button></div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div><label class="label">شماره چک *</label><input class="input-text" :name="`checks[${index}][check_number]`" x-model="check.check_number" required></div>
                            <div><label class="label">شناسه صیادی ۱۶ رقمی</label><input class="input-text font-mono" :name="`checks[${index}][sayad_id]`" x-model="check.sayad_id" maxlength="16" inputmode="numeric"></div>
                            <div><label class="label">بانک *</label><input class="input-text" :name="`checks[${index}][bank_name]`" x-model="check.bank_name" required></div>
                            <div><label class="label">صاحب حساب</label><input class="input-text" :name="`checks[${index}][account_owner]`" x-model="check.account_owner"></div>
                            <div><label class="label">مبلغ چک (تومان) *</label><input class="input-text font-mono" type="number" min="1" :name="`checks[${index}][amount_toman]`" x-model="check.amount_toman" required></div>
                            <div><label class="label">تاریخ سررسید *</label><input class="input-text font-mono" type="text" data-jdp autocomplete="off" :name="`checks[${index}][due_date]`" x-model="check.due_date" required></div>
                        </div>
                    </div>
                </template>
                <button type="button" @click="checks.push({check_number:'',sayad_id:'',bank_name:'',account_owner:'',amount_toman:'',due_date:''})" class="button-secondary w-full border-dashed text-blue-700">+ افزودن چک دیگر</button>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('purchases.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">ذخیره پیش‌نویس خرید</button>
        </div>
    </form>
</div>
@endsection

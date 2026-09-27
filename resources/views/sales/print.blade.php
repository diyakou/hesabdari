<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاکتور {{ $invoice->invoice_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body { background: white !important; color: black !important; }
            .no-print { display: none !important; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased p-4 sm:p-8 font-sans">
    <div class="no-print mb-6 max-w-3xl mx-auto flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold">قالب چاپ:</span>
            <a href="{{ route('sales.print', ['invoice' => $invoice, 'format' => 'a4']) }}" @class(['px-3 py-1 text-xs rounded-lg border', 'bg-teal-700 text-white border-teal-700' => $format === 'a4', 'bg-white text-slate-700' => $format !== 'a4'])>
                فاکتور A4
            </a>
            <a href="{{ route('sales.print', ['invoice' => $invoice, 'format' => 'thermal']) }}" @class(['px-3 py-1 text-xs rounded-lg border', 'bg-teal-700 text-white border-teal-700' => $format === 'thermal', 'bg-white text-slate-700' => $format !== 'thermal'])>
                فیش پرینتر ۸۰mm
            </a>
        </div>
        <button onclick="window.print()" class="button-primary bg-teal-700 hover:bg-teal-800">
            🖨️ چاپ اکنون
        </button>
    </div>

    @if($format === 'thermal')
        {{-- 80mm Thermal Receipt Layout --}}
        <div class="w-[80mm] mx-auto bg-white p-4 border border-slate-300 shadow-sm text-xs font-mono">
            <div class="text-center border-b border-dashed border-slate-300 pb-3 mb-3">
                <h2 class="text-sm font-bold">{{ config('app.name', 'فروشگاه تخصصی موبایل') }}</h2>
                <p class="text-[10px] text-slate-500 mt-1">فاکتور فروش کالا و خدمات</p>
                <div class="mt-2 text-[11px] flex justify-between">
                    <span>شماره: {{ $invoice->invoice_number }}</span>
                    <span>{{ $invoice->issue_date->format('Y-m-d') }}</span>
                </div>
                <div class="text-[11px] text-right mt-1">
                    <span>مشتری: {{ $invoice->party?->name }}</span>
                    @if($invoice->party?->mobile)
                        <span class="block" dir="ltr">{{ $invoice->party->mobile }}</span>
                    @endif
                </div>
            </div>

            <div class="space-y-2 border-b border-dashed border-slate-300 pb-3 mb-3">
                @foreach($invoice->lines as $line)
                    <div class="border-b border-slate-100 pb-1.5">
                        <div class="font-bold text-slate-900">{{ $line->product?->name }}</div>
                        @if($line->device)
                            <div class="text-[10px] text-slate-600 font-mono" dir="ltr">
                                IMEI: {{ $line->device->primary_imei }}
                            </div>
                        @endif
                        <div class="flex justify-between text-[11px] mt-1">
                            <span>{{ $line->quantity }} × {{ number_format($line->unit_price_rials / 10) }}</span>
                            <span class="font-bold">{{ number_format((($line->quantity * $line->unit_price_rials) - $line->discount_rials) / 10) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="space-y-1.5 text-[11px] border-b border-dashed border-slate-300 pb-3 mb-3">
                <div class="flex justify-between">
                    <span>جمع ناخالص:</span>
                    <span>{{ number_format($invoice->subtotal_rials / 10) }} تومان</span>
                </div>
                @if($invoice->discount_rials > 0)
                    <div class="flex justify-between text-rose-600">
                        <span>تخفیف:</span>
                        <span>{{ number_format($invoice->discount_rials / 10) }} تومان</span>
                    </div>
                @endif
                <div class="flex justify-between font-bold text-xs pt-1 border-t border-slate-200">
                    <span>مبلغ کل:</span>
                    <span>{{ number_format($invoice->total_amount_rials / 10) }} تومان</span>
                </div>
                @php
                    $paidRials = $invoice->allocations->sum('amount_rials');
                    $remRials = max(0, $invoice->total_amount_rials - $paidRials);
                @endphp
                <div class="flex justify-between">
                    <span>پرداخت شده:</span>
                    <span>{{ number_format($paidRials / 10) }} تومان</span>
                </div>
                @if($remRials > 0)
                    <div class="flex justify-between text-amber-700 font-bold">
                        <span>مانده حساب:</span>
                        <span>{{ number_format($remRials / 10) }} تومان</span>
                    </div>
                @endif
            </div>

            <div class="text-[10px] text-center text-slate-500 space-y-1">
                <p>مهلت تست گوشی‌های کارکرده طبق توافق ۲۴ ساعت می‌باشد.</p>
                <p>از خرید شما سپاسگزاریم.</p>
            </div>
        </div>
    @else
        {{-- Standard A4 Invoice Layout --}}
        <div class="max-w-3xl mx-auto bg-white p-8 border border-slate-200 shadow-sm rounded-xl">
            <div class="flex items-center justify-between border-b-2 border-slate-900 pb-6 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">{{ config('app.name', 'فروشگاه تخصصی موبایل') }}</h1>
                    <p class="text-xs text-slate-500 mt-1">فاکتور رسمی فروش کالا و خدمات</p>
                </div>
                <div class="text-left text-xs space-y-1 font-mono">
                    <div><span class="text-slate-500">شماره فاکتور:</span> <span class="font-bold text-sm text-slate-900" dir="ltr">{{ $invoice->invoice_number }}</span></div>
                    <div><span class="text-slate-500">تاریخ:</span> <span dir="ltr">{{ $invoice->issue_date->format('Y-m-d') }}</span></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-lg mb-6 text-xs">
                <div>
                    <span class="text-slate-500 block">مشخصات خریدار:</span>
                    <span class="font-bold text-sm text-slate-900 block mt-1">{{ $invoice->party?->name }}</span>
                    @if($invoice->party?->mobile)
                        <span class="text-slate-600 font-mono block mt-0.5" dir="ltr">موبایل: {{ $invoice->party->mobile }}</span>
                    @endif
                    @if($invoice->party?->address)
                        <span class="text-slate-600 block mt-0.5">{{ $invoice->party->address }}</span>
                    @endif
                </div>
                <div class="text-left">
                    <span class="text-slate-500 block">فروشگاه صادرکننده:</span>
                    <span class="font-bold text-slate-900 block mt-1">شعبه مرکزی</span>
                    <span class="text-slate-600 block mt-0.5">انبار: {{ $invoice->warehouse?->name ?: 'اصلی' }}</span>
                </div>
            </div>

            <table class="w-full text-right text-xs mb-6 border border-slate-200">
                <thead class="bg-slate-100 border-b border-slate-200 font-semibold text-slate-700">
                    <tr>
                        <th class="p-3 w-12 text-center">ردیف</th>
                        <th class="p-3">شرح کالا یا خدمت</th>
                        <th class="p-3 w-20 text-center">تعداد</th>
                        <th class="p-3 w-32">قیمت واحد (تومان)</th>
                        <th class="p-3 w-24">تخفیف (تومان)</th>
                        <th class="p-3 w-36">مبلغ نهایی (تومان)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoice->lines as $index => $line)
                        <tr>
                            <td class="p-3 text-center font-mono">{{ $index + 1 }}</td>
                            <td class="p-3">
                                <span class="font-bold text-slate-900">{{ $line->product?->name }}</span>
                                @if($line->variant?->color || $line->variant?->storage)
                                    <span class="text-slate-500">({{ $line->variant->color }} - {{ $line->variant->storage }})</span>
                                @endif
                                @if($line->device)
                                    <div class="font-mono text-teal-800 text-[11px] mt-0.5" dir="ltr">
                                        IMEI: {{ $line->device->primary_imei }}
                                        @if($line->device->secondary_imei)
                                            / IMEI2: {{ $line->device->secondary_imei }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="p-3 text-center font-mono">{{ $line->quantity }}</td>
                            <td class="p-3 font-mono">{{ number_format($line->unit_price_rials / 10) }}</td>
                            <td class="p-3 font-mono text-rose-600">{{ $line->discount_rials ? number_format($line->discount_rials / 10) : '۰' }}</td>
                            <td class="p-3 font-mono font-bold text-slate-900">
                                {{ number_format((($line->quantity * $line->unit_price_rials) - $line->discount_rials) / 10) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex justify-end mb-6">
                <div class="w-72 bg-slate-50 p-4 rounded-lg border border-slate-200 text-xs space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-500">جمع ناخالص:</span>
                        <span class="font-mono">{{ number_format($invoice->subtotal_rials / 10) }} تومان</span>
                    </div>
                    @if($invoice->discount_rials > 0)
                        <div class="flex justify-between text-rose-600">
                            <span>تخفیف کل:</span>
                            <span class="font-mono">- {{ number_format($invoice->discount_rials / 10) }} تومان</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-sm text-teal-800 border-t border-slate-200 pt-2">
                        <span>مبلغ قابل پرداخت:</span>
                        <span class="font-mono">{{ number_format($invoice->total_amount_rials / 10) }} تومان</span>
                    </div>
                    @php
                        $paidRials = $invoice->allocations->sum('amount_rials');
                        $remRials = max(0, $invoice->total_amount_rials - $paidRials);
                    @endphp
                    <div class="flex justify-between border-t border-slate-200 pt-1 text-slate-700">
                        <span>مبلغ دریافتی:</span>
                        <span class="font-mono font-bold text-emerald-600">{{ number_format($paidRials / 10) }} تومان</span>
                    </div>
                    @if($remRials > 0)
                        <div class="flex justify-between text-amber-700 font-bold">
                            <span>مانده بدهی:</span>
                            <span class="font-mono">{{ number_format($remRials / 10) }} تومان</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4 text-[11px] text-slate-500 flex justify-between items-center">
                <div>
                    <p>شرایط ضمانت: کالاهای مصرفی (گلس و کابل) فاقد مهلت تست می‌باشند. گوشی‌ها طبق فاکتور مشمول ۲۴ ساعت مهلت تست سخت‌افزاری هستند.</p>
                </div>
                <div class="text-left font-mono">
                    <span>امضاء خریدار</span>
                </div>
            </div>
        </div>
    @endif
</body>
</html>

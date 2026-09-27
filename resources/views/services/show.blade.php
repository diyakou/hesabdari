@extends('layouts.app')

@section('title', 'جزئیات سفارش خدمت ' . $order->order_number)
@section('page-heading', 'سفارش خدمت: ' . $order->order_number)
@section('page-description', 'مشاهده اطلاعات پذیرش، پیگیری وضعیت، ثبت اقدامات و تحویل به مشتری')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="font-mono text-xl font-bold text-slate-900" dir="ltr">{{ $order->order_number }}</span>
            @php
                $badgeClasses = match($order->status) {
                    'queued' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'ready' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'delivered' => 'bg-purple-50 text-purple-700 border-purple-200',
                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                };
            @endphp
            <span class="rounded-md px-2.5 py-1 text-xs font-semibold border {{ $badgeClasses }}">
                {{ $order->statusLabel() }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('services.index') }}" class="button-secondary">بازگشت به فهرست</a>
        </div>
    </div>

    <!-- Status Change Actions -->
    @if(! in_array($order->status, ['delivered', 'cancelled'], true))
        <div class="rounded-xl border border-teal-100 bg-teal-50/50 p-4 shadow-sm">
            <h3 class="text-xs font-bold text-teal-900 uppercase tracking-wider mb-3">عملیات تغییر وضعیت کار</h3>
            <div class="flex flex-wrap items-center gap-3">
                @if($order->status === 'queued')
                    <form method="POST" action="{{ route('services.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="in_progress">
                        <button type="submit" class="button-primary">
                            شروع انجام کار (در حال انجام)
                        </button>
                    </form>
                @elseif($order->status === 'in_progress')
                    <form method="POST" action="{{ route('services.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="ready">
                        <button type="submit" class="button-primary bg-emerald-600 hover:bg-emerald-700">
                            اتمام کار و آماده‌سازی برای تحویل
                        </button>
                    </form>
                @elseif($order->status === 'ready')
                    <form method="POST" action="{{ route('services.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="delivered">
                        <button type="submit" class="button-primary bg-purple-600 hover:bg-purple-700">
                            ثبت تحویل قطعی به مشتری
                        </button>
                    </form>
                @endif

                <div x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="button-secondary text-rose-600 border-rose-200 hover:bg-rose-50">
                        لغو سفارش خدمت
                    </button>

                    <div x-show="open" class="mt-4 p-4 bg-white rounded-lg border border-rose-200 shadow-sm space-y-3">
                        <form method="POST" action="{{ route('services.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">دلیل لغو خدمت <span class="text-rose-500">*</span></label>
                                <input type="text" name="cancellation_reason" required class="input-text w-full text-xs" placeholder="مثلاً: عدم موافقت مشتری با هزینه قطعه یا منصرف شدن">
                            </div>
                            <div class="flex justify-end gap-2 mt-3">
                                <button type="button" @click="open = false" class="button-secondary text-xs">انصراف</button>
                                <button type="submit" class="button-primary bg-rose-600 hover:bg-rose-700 text-xs">تأیید لغو</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">اطلاعات پذیرش و مشخصات فنی</h3>
                
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">نوع خدمت</dt>
                        <dd class="font-semibold text-slate-800 mt-0.5">{{ $order->serviceDefinition?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">تکنسین مسئول</dt>
                        <dd class="font-medium text-slate-800 mt-0.5">{{ $order->technician?->name ?: 'تعیین نشده' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">تاریخ ثبت</dt>
                        <dd class="font-mono text-slate-700 mt-0.5">{{ persian_date($order->created_at, true) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">تاریخ تحویل موعود</dt>
                        <dd class="font-mono text-slate-700 mt-0.5">{{ $order->promised_date ? persian_date($order->promised_date) : 'تعیین نشده' }}</dd>
                    </div>
                    @if($order->delivered_date)
                        <div>
                            <dt class="text-xs text-slate-500">تاریخ تحویل واقعی</dt>
                            <dd class="font-mono text-slate-700 mt-0.5">{{ persian_date($order->delivered_date, true) }}</dd>
                        </div>
                    @endif
                    @if($order->cancellation_reason)
                        <div class="sm:col-span-2 bg-rose-50 p-3 rounded-lg border border-rose-200">
                            <dt class="text-xs font-bold text-rose-700">دلیل لغو</dt>
                            <dd class="text-sm text-rose-800 mt-1">{{ $order->cancellation_reason }}</dd>
                        </div>
                    @endif
                </dl>

                @if($order->notes)
                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-xs text-slate-500 block mb-1">یادداشت‌ها</span>
                        <p class="text-sm text-slate-700 bg-slate-50 p-3 rounded-lg">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Dynamic Form Data Fields -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">فیلدهای اختصاصی پذیرش (نسخه {{ $order->formVersion?->version ?? 1 }})</h3>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    @php
                        $schema = $order->formVersion?->fields_schema ?? [];
                        $schemaMap = collect($schema)->keyBy('name');
                    @endphp

                    @forelse($order->form_data ?? [] as $key => $val)
                        @php
                            $label = $schemaMap->get($key)['label'] ?? $key;
                        @endphp
                        <div class="bg-slate-50 p-3 rounded-lg">
                            <dt class="text-xs text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-slate-800 mt-1">
                                @if(is_bool($val))
                                    <span class="{{ $val ? 'text-emerald-700 font-bold' : 'text-slate-400' }}">
                                        {{ $val ? '✓ بله (تأیید شده)' : 'خیر' }}
                                    </span>
                                @elseif(is_array($val))
                                    {{ implode(', ', $val) }}
                                @else
                                    {{ $val ?: '—' }}
                                @endif
                            </dd>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 col-span-2">اطلاعات اختصاصی ثبت نشده است.</p>
                    @endforelse
                </dl>
            </div>
        </div>

        <!-- Sidebar / Customer & Financial info -->
        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">اطلاعات مشتری</h3>
                <div>
                    <span class="text-xs text-slate-500 block">نام شخص</span>
                    <a href="{{ route('parties.show', $order->party) }}" class="font-bold text-teal-700 hover:underline">
                        {{ $order->party?->name }}
                    </a>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">شماره تماس</span>
                    <span class="font-mono text-sm text-slate-700" dir="ltr">{{ $order->party?->phone ?: '—' }}</span>
                </div>
            </div>

            <!-- Financials (Manager & Accountant only) -->
            @can('canSeeFinancials', App\Models\User::class)
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">بهای تمام‌شده و وضعیت مالی</h3>
                    <div>
                        <span class="text-xs text-slate-500 block">تعرفه پایه خدمت</span>
                        <span class="font-mono font-bold text-slate-800">
                            {{ number_format(($order->serviceDefinition?->default_fee_rials ?? 0) / 10) }} تومان
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">هزینه مستقیم قطعه / اکانت</span>
                        <span class="font-mono font-bold text-rose-700">
                            {{ number_format($order->direct_cost_rials / 10) }} تومان
                        </span>
                    </div>
                    @if($order->invoice)
                        <div class="pt-3 border-t border-slate-100">
                            <span class="text-xs text-slate-500 block">فاکتور فروش صادرشده</span>
                            <a href="{{ route('sales.show', $order->invoice) }}" class="font-mono text-sm font-bold text-teal-700 hover:underline" dir="ltr">
                                {{ $order->invoice->invoice_number }}
                            </a>
                        </div>
                    @endif
                </div>
            @endcan
        </div>
    </div>
</div>
@endsection

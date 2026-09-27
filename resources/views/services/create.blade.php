@extends('layouts.app')

@section('title', 'پذیرش خدمت جدید')
@section('page-heading', 'پذیرش خدمت جدید')
@section('page-description', 'ثبت درخواست خدمات فنی، نرم‌افزاری، انتقال دیتا و تعمیرات با فرم‌های پویا')

@section('content')
<div class="mx-auto max-w-4xl" x-data="{
    selectedServiceId: '{{ old('service_definition_id', $services->first()?->id ?? '') }}',
    selectedCategory: '{{ $services->firstWhere('id', old('service_definition_id'))?->category ?? $services->first()?->category ?? 'software' }}',
    services: {{ Js::from($services) }},
    get filteredServices() { return this.services.filter(s => s.category === this.selectedCategory); },
    setCategory(category) {
        this.selectedCategory = category;
        if (!this.filteredServices.some(s => s.id == this.selectedServiceId)) this.selectedServiceId = String(this.filteredServices[0]?.id || '');
    },
    get activeService() {
        return this.services.find(s => s.id == this.selectedServiceId);
    },
    get activeSchema() {
        return this.activeService?.latest_form_version?.fields_schema || [];
    }
}">
    <form method="POST" action="{{ route('services.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <h2 class="text-base font-bold text-slate-800">اطلاعات اولیه خدمت</h2>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        مشتری <span class="text-rose-500">*</span>
                    </label>
                    <select name="party_id" class="input-text w-full" required>
                        <option value="">-- انتخاب مشتری --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('party_id') == $customer->id)>
                                {{ $customer->name }} ({{ $customer->phone ?: 'بدون شماره' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        نوع خدمت <span class="text-rose-500">*</span>
                    </label>
                    <div class="mb-3 grid grid-cols-2 gap-2 rounded-xl bg-blue-50 p-1.5">
                        <button type="button" @click="setCategory('software')" :class="selectedCategory === 'software' ? 'bg-blue-600 text-white shadow-sm' : 'text-blue-800 hover:bg-white'" class="rounded-lg px-4 py-2 text-xs font-bold transition">خدمات نرم‌افزاری</button>
                        <button type="button" @click="setCategory('hardware')" :class="selectedCategory === 'hardware' ? 'bg-blue-600 text-white shadow-sm' : 'text-blue-800 hover:bg-white'" class="rounded-lg px-4 py-2 text-xs font-bold transition">تعمیرات سخت‌افزاری</button>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="service in filteredServices" :key="service.id">
                            <label class="cursor-pointer rounded-xl border p-3 transition" :class="selectedServiceId == service.id ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-100' : 'border-blue-100 bg-white hover:border-blue-300'">
                                <input class="sr-only" type="radio" name="service_definition_id" :value="service.id" x-model="selectedServiceId" required>
                                <span class="block text-xs font-extrabold text-slate-800" x-text="service.name"></span>
                                <span class="mt-1 block text-[10px] leading-5 text-slate-500" x-text="service.description"></span>
                                <span class="mt-2 block text-[10px] font-bold text-blue-700" x-text="service.default_fee_rials ? new Intl.NumberFormat('fa-IR').format(service.default_fee_rials / 10) + ' تومان' : 'پس از کارشناسی'"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        تکنسین / کارشناس مسئول
                    </label>
                    <select name="technician_id" class="input-text w-full">
                        <option value="">-- بدون تعیین (عمومی) --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" @selected(old('technician_id') == $tech->id)>
                                {{ $tech->name }} ({{ $tech->role->persianLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        تاریخ تحویل موعود
                    </label>
                    <input
                        type="date"
                        name="promised_date"
                        value="{{ old('promised_date') }}"
                        class="input-text w-full font-mono"
                        dir="ltr"
                    >
                </div>

                @if(auth()->user()->canSeeFinancials())
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            هزینه مستقیم خدمت (تومان)
                            <span class="text-xs text-slate-400 font-normal">مانند خرید اکانت، قطعه مصرفی</span>
                        </label>
                        <input
                            type="number"
                            name="direct_cost_toman"
                            value="{{ old('direct_cost_toman', 0) }}"
                            min="0"
                            class="input-text w-full font-mono"
                            placeholder="۰"
                        >
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">توضیحات و یادداشت</label>
                <textarea name="notes" rows="2" class="input-text w-full" placeholder="توضیحات یا نیازمندی‌های خاص مشتری...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <!-- Dynamic Form Fields -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <div>
                <h2 class="text-base font-bold text-slate-800">مشخصات و فرم پذیرش</h2>
                <p class="text-xs text-slate-500 mt-1" x-text="activeService?.description || 'مشخصات لازم برای اجرای خدمت'"></p>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <template x-for="field in activeSchema" :key="field.name">
                    <div class="space-y-1" :class="{'sm:col-span-2': ['textarea', 'checklist'].includes(field.type)}">
                        <label class="block text-sm font-medium text-slate-700">
                            <span x-text="field.label"></span>
                            <span x-show="field.required" class="text-rose-500">*</span>
                        </label>

                        <!-- Text -->
                        <template x-if="field.type === 'text'">
                            <input
                                type="text"
                                :name="'form_data[' + field.name + ']'"
                                :required="field.required"
                                class="input-text w-full"
                            >
                        </template>

                        <!-- Number -->
                        <template x-if="field.type === 'number'">
                            <input
                                type="number"
                                :name="'form_data[' + field.name + ']'"
                                :required="field.required"
                                class="input-text w-full font-mono"
                            >
                        </template>

                        <!-- Select -->
                        <template x-if="field.type === 'select'">
                            <select
                                :name="'form_data[' + field.name + ']'"
                                :required="field.required"
                                class="input-text w-full"
                            >
                                <option value="">-- انتخاب کنید --</option>
                                <template x-for="opt in (field.options || [])" :key="opt">
                                    <option :value="opt" x-text="opt"></option>
                                </template>
                            </select>
                        </template>

                        <!-- Textarea -->
                        <template x-if="field.type === 'textarea'">
                            <textarea
                                :name="'form_data[' + field.name + ']'"
                                :required="field.required"
                                rows="3"
                                class="input-text w-full"
                            ></textarea>
                        </template>

                        <!-- Boolean / Checkbox -->
                        <template x-if="field.type === 'boolean'">
                            <label class="flex items-center gap-2 mt-2">
                                <input
                                    type="checkbox"
                                    :name="'form_data[' + field.name + ']'"
                                    value="1"
                                    :required="field.required"
                                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                >
                                <span class="text-xs text-slate-600">تأیید و موافقت</span>
                            </label>
                        </template>

                        <!-- Multi-value checklist -->
                        <template x-if="field.type === 'checklist'">
                            <div class="grid gap-2 sm:grid-cols-3">
                                <template x-for="opt in (field.options || [])" :key="opt">
                                    <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-blue-100 px-3 text-xs text-slate-700 transition hover:border-blue-400 hover:bg-blue-50">
                                        <input type="checkbox" :name="'form_data[' + field.name + '][]'" :value="opt" class="size-4 rounded border-blue-200 text-blue-600 focus:ring-blue-500">
                                        <span x-text="opt"></span>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('services.index') }}" class="button-secondary">انصراف</a>
            <button type="submit" class="button-primary">ثبت و ایجاد سفارش خدمت</button>
        </div>
    </form>
</div>
@endsection

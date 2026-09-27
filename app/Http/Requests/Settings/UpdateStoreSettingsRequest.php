<?php

namespace App\Http\Requests\Settings;

use App\Support\Localization\LocalizedDigits;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'store_phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'branch_name' => ['required', 'string', 'max:120'],
            'branch_phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'branch_address' => ['nullable', 'string', 'max:1000'],
            'warehouse_name' => ['required', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'store_name' => 'نام فروشگاه',
            'legal_name' => 'نام حقوقی',
            'store_phone' => 'تلفن فروشگاه',
            'store_address' => 'نشانی فروشگاه',
            'branch_name' => 'نام شعبه',
            'branch_phone' => 'تلفن شعبه',
            'branch_address' => 'نشانی شعبه',
            'warehouse_name' => 'نام انبار',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'store_phone' => LocalizedDigits::toAscii($this->input('store_phone')),
            'branch_phone' => LocalizedDigits::toAscii($this->input('branch_phone')),
        ]);
    }
}

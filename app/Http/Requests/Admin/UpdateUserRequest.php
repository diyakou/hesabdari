<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function ($validator): void {
                /** @var User $target */
                $target = $this->route('user');

                if ($this->user()?->is($target)
                    && (! $this->boolean('is_active') || $this->input('role') !== UserRole::Manager->value)) {
                    $validator->errors()->add('is_active', 'مدیر نمی‌تواند نقش یا وضعیت حساب خودش را کاهش دهد.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = LocalizedDigits::toAscii($this->input('email'));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => is_string($email) ? Str::lower(trim($email)) : $email,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}

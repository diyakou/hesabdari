<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Support\Localization\LocalizedDigits;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'ایجاد حساب مدیر سامانه به‌صورت تعاملی';

    public function handle(AuditLogger $auditLogger): int
    {
        $name = trim((string) $this->ask('نام مدیر'));
        $emailInput = LocalizedDigits::toAscii($this->ask('ایمیل مدیر'));
        $email = is_string($emailInput) ? Str::lower(trim($emailInput)) : '';
        $password = (string) $this->secret('رمز عبور (حداقل ۱۰ نویسه و شامل حرف و عدد)');
        $passwordConfirmation = (string) $this->secret('تکرار رمز عبور');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($auditLogger, $email, $name, $password): void {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => UserRole::Manager,
                'is_active' => true,
            ]);

            $auditLogger->record(
                'user.created_by_command',
                $user,
                [],
                $user->only(['name', 'email', 'role', 'is_active']),
            );
        }, attempts: 3);

        $this->info('حساب مدیر با موفقیت ایجاد شد.');

        return self::SUCCESS;
    }
}

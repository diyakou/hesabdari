<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_interactively_creates_active_manager_without_logging_password(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('نام مدیر', 'مدیر سامانه')
            ->expectsQuestion('ایمیل مدیر', 'admin@example.test')
            ->expectsQuestion('رمز عبور (حداقل ۱۰ نویسه و شامل حرف و عدد)', 'command-pass-123')
            ->expectsQuestion('تکرار رمز عبور', 'command-pass-123')
            ->expectsOutput('حساب مدیر با موفقیت ایجاد شد.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame(UserRole::Manager, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('command-pass-123', $user->password));

        $audit = AuditLog::query()->where('action', 'user.created_by_command')->firstOrFail();

        $this->assertNull($audit->actor_user_id);
        $this->assertArrayNotHasKey('password', $audit->changes['after']);
        $this->assertStringNotContainsString('command-pass-123', json_encode($audit->changes, JSON_THROW_ON_ERROR));
    }

    public function test_command_rejects_duplicate_email_and_weak_password(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);

        $this->artisan('app:create-admin')
            ->expectsQuestion('نام مدیر', 'مدیر دوم')
            ->expectsQuestion('ایمیل مدیر', 'existing@example.test')
            ->expectsQuestion('رمز عبور (حداقل ۱۰ نویسه و شامل حرف و عدد)', 'short')
            ->expectsQuestion('تکرار رمز عبور', 'short')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}

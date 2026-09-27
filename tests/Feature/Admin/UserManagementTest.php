<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_user_list_and_creation_form(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.users.create'))->assertOk();
    }

    public function test_manager_can_create_user_with_normalized_email_and_audit_record(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => '  کاربر فروش  ',
            'email' => 'USER۱@EXAMPLE.TEST',
            'password' => 'strong-pass-123',
            'password_confirmation' => 'strong-pass-123',
            'role' => UserRole::Salesperson->value,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'user1@example.test')->firstOrFail();

        $this->assertSame('کاربر فروش', $user->name);
        $this->assertSame(UserRole::Salesperson, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('strong-pass-123', $user->password));

        $audit = AuditLog::query()->where('action', 'user.created')->firstOrFail();

        $this->assertTrue($audit->actor->is($manager));
        $this->assertSame($user->getKey(), $audit->auditable_id);
        $this->assertArrayNotHasKey('password', $audit->changes['after']);
        $this->assertStringNotContainsString('strong-pass-123', json_encode($audit->changes, JSON_THROW_ON_ERROR));
    }

    public function test_manager_can_update_and_deactivate_another_user_without_replacing_password(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->create([
            'email' => 'employee@example.test',
            'password' => 'old-password-123',
        ]);
        $passwordHash = $user->password;

        $this->actingAs($manager)->put(route('admin.users.update', $user), [
            'name' => 'حسابدار فروشگاه',
            'email' => 'ACCOUNTANT@EXAMPLE.TEST',
            'password' => '',
            'password_confirmation' => '',
            'role' => UserRole::Accountant->value,
            'is_active' => '0',
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('accountant@example.test', $user->email);
        $this->assertSame(UserRole::Accountant, $user->role);
        $this->assertFalse($user->is_active);
        $this->assertSame($passwordHash, $user->password);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.updated',
            'actor_user_id' => $manager->getKey(),
            'auditable_id' => $user->getKey(),
        ]);
    }

    public function test_manager_cannot_demote_or_deactivate_their_own_account(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->from(route('admin.users.edit', $manager))
            ->put(route('admin.users.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'role' => UserRole::Salesperson->value,
                'is_active' => '0',
                'password' => '',
                'password_confirmation' => '',
            ])->assertRedirect(route('admin.users.edit', $manager))
            ->assertSessionHasErrors('is_active');

        $manager->refresh();

        $this->assertSame(UserRole::Manager, $manager->role);
        $this->assertTrue($manager->is_active);
    }

    public function test_user_management_input_is_validated(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->post(route('admin.users.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'different',
                'role' => 'owner',
                'is_active' => '1',
            ])->assertSessionHasErrors(['name', 'email', 'password', 'role']);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}

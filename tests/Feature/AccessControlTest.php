<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_internal_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('settings.edit'))->assertRedirect(route('login'));
    }

    public function test_non_manager_cannot_manage_users_or_store_settings(): void
    {
        $user = User::factory()->create(['role' => UserRole::Accountant]);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('settings.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('settings.update'), [])->assertForbidden();
    }

    public function test_active_non_manager_can_open_dashboard_without_manager_navigation(): void
    {
        $user = User::factory()->create(['role' => UserRole::Salesperson]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('کاربران و دسترسی‌ها')
            ->assertDontSee('تنظیمات فروشگاه');
    }

    public function test_user_deactivated_after_login_is_forced_out(): void
    {
        $user = User::factory()->create();
        $user->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('ورود به سامانه');
    }

    public function test_active_user_can_log_in_and_session_id_is_regenerated(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@example.test',
            'password' => 'secure-password-123',
        ]);

        $session = app('session.store');
        $session->start();
        $previousSessionId = $session->getId();

        $response = $this->post(route('login.store'), [
            'email' => 'MANAGER@EXAMPLE.TEST',
            'password' => 'secure-password-123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, $session->getId());
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.test',
            'password' => 'secure-password-123',
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'inactive@example.test',
            'password' => 'secure-password-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_invalid_login_attempts_are_rate_limited(): void
    {
        $credentials = [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ];

        RateLimiter::clear('unknown@example.test|127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), $credentials)
                ->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.store'), $credentials);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'بیش از حد',
            $response->getSession()->get('errors')->first('email'),
        );
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'با موفقیت خارج شدید.');

        $this->assertGuest();
    }

    public function test_public_registration_routes_do_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }
}

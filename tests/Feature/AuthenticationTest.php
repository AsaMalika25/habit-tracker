<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
        $this->get(route('habits.index'))->assertRedirectToRoute('login');
        $this->get(route('memories.keepsakes'))->assertRedirectToRoute('login');
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Alya Putri',
            'email' => 'alya@example.com',
            'timezone' => 'Asia/Jakarta',
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
            'role' => 'admin',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Alya Putri',
            'email' => 'alya@example.com',
            'timezone' => 'Asia/Jakarta',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'alya@example.com',
            'password' => 'Secure123',
        ]);

        $this->post(route('login'), [
            'email' => 'ALYA@example.com',
            'password' => 'Secure123',
            'remember' => true,
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirectToRoute('login');
        $this->assertGuest();
    }

    public function test_invalid_login_is_rejected_with_a_generic_error(): void
    {
        User::factory()->create(['email' => 'alya@example.com']);

        $this->from(route('login'))->post(route('login'), [
            'email' => 'alya@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $email = 'limited@example.com';
        $key = Str::transliterate(Str::lower($email).'|127.0.0.1');
        RateLimiter::clear($key);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login'), [
            'email' => $email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_open_guest_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('login'))->assertRedirectToRoute('dashboard');
        $this->get(route('register'))->assertRedirectToRoute('dashboard');
    }
}

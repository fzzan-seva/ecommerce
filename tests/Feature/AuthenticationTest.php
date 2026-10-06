<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_normal_customer_and_logs_them_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '081234567890',
            'password' => 'Str0ng-Pass-9x',
            'password_confirmation' => 'Str0ng-Pass-9x',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertTrue(Hash::check('Str0ng-Pass-9x', $user->password));
    }

    public function test_registration_cannot_escalate_the_role_field(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'password' => 'Str0ng-Pass-9x',
            'password_confirmation' => 'Str0ng-Pass-9x',
            'role' => 'admin',
        ]);

        $this->assertSame('user', User::where('email', 'sneaky@example.com')->firstOrFail()->role);
    }

    public function test_registration_requires_a_strong_enough_password(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Weak User',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_login_with_valid_credentials_authenticates_the_user(): void
    {
        $user = $this->makeCustomer(false);
        $user->password = Hash::make('Str0ng-Pass-9x');
        $user->save();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Str0ng-Pass-9x',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_a_wrong_password_is_rejected(): void
    {
        $user = $this->makeCustomer(false);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'definitely-wrong',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->makeCustomer(false);

        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password-'.$i,
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = $this->makeCustomer();

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        // The old session cookie must no longer authenticate.
        $this->withSession([])->get('/');
        $this->assertGuest();
    }

    public function test_guests_are_redirected_from_protected_pages(): void
    {
        $this->get('/keranjang')->assertRedirect('/login');
        $this->get('/checkout')->assertRedirect('/login');
        $this->get('/pesanan')->assertRedirect('/login');
        $this->get('/alamat')->assertRedirect('/login');
    }
}

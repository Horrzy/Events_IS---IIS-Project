<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login_by_username_or_email(): void
    {
        $response = $this->post('/registrace', [
            'username' => 'karel',
            'name' => 'Karel Nový',
            'email' => 'karel@example.test',
            'password' => 'TajneHeslo123',
            'password_confirmation' => 'TajneHeslo123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'karel',
            'role' => 'user',
            'is_active' => 1,
        ]);

        $this->post('/odhlaseni')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/prihlaseni', [
            'login' => 'karel',
            'password' => 'TajneHeslo123',
        ])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = new User([
            'username' => 'blokovany',
            'name' => 'Blokovaný',
            'email' => 'blok@example.test',
            'password' => 'Heslo1234!',
        ]);
        $user->role = 'user';
        $user->is_active = false;
        $user->save();

        $this->post('/prihlaseni', [
            'login' => 'blokovany',
            'password' => 'Heslo1234!',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_inactivity_timeout_logs_user_out(): void
    {
        $user = new User([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.test',
            'password' => 'Heslo1234!',
        ]);
        $user->role = 'user';
        $user->is_active = true;
        $user->save();

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(45)->timestamp])
            ->get('/profil')
            ->assertRedirect('/prihlaseni');

        $this->assertGuest();
    }

    public function test_admin_panel_is_forbidden_for_regular_user_and_allowed_for_admin(): void
    {
        $regular = new User([
            'username' => 'obycejny',
            'name' => 'Obyčejný',
            'email' => 'obycejny@example.test',
            'password' => 'Heslo1234!',
        ]);
        $regular->role = 'user';
        $regular->save();

        $admin = new User([
            'username' => 'spravce',
            'name' => 'Správce',
            'email' => 'spravce@example.test',
            'password' => 'Heslo1234!',
        ]);
        $admin->role = 'admin';
        $admin->save();

        $this->actingAs($regular)->get('/admin/uzivatele')->assertStatus(403);
        $this->actingAs($admin)->get('/admin/uzivatele')->assertStatus(200);
    }
}

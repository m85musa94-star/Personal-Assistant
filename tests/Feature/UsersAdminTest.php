<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersAdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, bool $admin = false): User
    {
        $u = new User(['name' => $email, 'email' => $email, 'password' => 'correct-horse-1']);
        $u->is_admin = $admin;
        $u->save();

        return $u;
    }

    public function test_non_admin_is_forbidden(): void
    {
        $u = $this->user('u@example.com');
        $this->actingAs($u)->get('/users')->assertForbidden();
        $this->actingAs($u)->post('/users', ['name' => 'x', 'email' => 'x@example.com', 'password' => 'long-enough-pass'])->assertForbidden();
    }

    public function test_admin_creates_user_who_can_login(): void
    {
        $admin = $this->user('admin@example.com', true);
        $this->actingAs($admin)->post('/users', ['name' => 'سارة', 'email' => 'Sara@Example.com', 'password' => 'long-enough-pass'])->assertSessionHas('ok');
        $sara = User::where('email', 'sara@example.com')->first();
        $this->assertNotNull($sara);
        $this->assertFalse($sara->is_admin);
        auth()->logout();
        $this->post('/login', ['email' => 'sara@example.com', 'password' => 'long-enough-pass'])->assertRedirect('/');
    }

    public function test_duplicate_email_and_weak_password_rejected(): void
    {
        $admin = $this->user('admin@example.com', true);
        $this->actingAs($admin)->post('/users', ['name' => 'x', 'email' => 'ADMIN@example.com', 'password' => 'long-enough-pass'])->assertSessionHasErrors('email');
        $this->actingAs($admin)->post('/users', ['name' => 'x', 'email' => 'new@example.com', 'password' => 'short'])->assertSessionHasErrors('password');
    }

    public function test_admin_can_toggle_others_but_not_self(): void
    {
        $admin = $this->user('admin@example.com', true);
        $other = $this->user('o@example.com');
        $this->actingAs($admin)->post("/users/{$other->id}/toggle")->assertSessionHas('ok');
        $this->assertFalse($other->fresh()->is_active);
        $this->actingAs($admin)->post("/users/{$admin->id}/toggle")->assertSessionHasErrors('users');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_resets_password(): void
    {
        $admin = $this->user('admin@example.com', true);
        $other = $this->user('o@example.com');
        $this->actingAs($admin)->put("/users/{$other->id}/password", ['password' => 'brand-new-password'])->assertSessionHas('ok');
        $this->assertTrue(\Hash::check('brand-new-password', $other->fresh()->password));
    }
}

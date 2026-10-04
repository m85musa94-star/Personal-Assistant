<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $o = []): User
    {
        $u = new User(['name' => 'أحمد', 'email' => $o['email'] ?? 'a@example.com', 'password' => 'correct-horse-1']);
        $u->is_admin = $o['admin'] ?? false;
        $u->permissions = ['tasks.use'];
        $u->is_active = $o['active'] ?? true;
        $u->save();

        return $u;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->getJson('/api/state')->assertUnauthorized();
        $this->get('/tasks')->assertRedirect('/login');
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_login_page_renders_arabic_form(): void
    {
        $this->get('/login')->assertOk()->assertSee('تسجيل الدخول')->assertSee('name="email"', false);
    }

    public function test_login_success_and_open_app(): void
    {
        $this->user();
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'correct-horse-1'])->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('a@example.com');
        $this->get('/tasks')->assertOk()->assertSee('MARKAZ_USER', false);
    }

    public function test_email_is_case_insensitive_on_login(): void
    {
        $this->user();
        $this->post('/login', ['email' => '  A@Example.COM ', 'password' => 'correct-horse-1'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->user();
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->user(['active' => false]);
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'correct-horse-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_with_open_session_is_kicked_out(): void
    {
        $u = $this->user();
        $this->actingAs($u)->get('/')->assertOk();
        $u->is_active = false;
        $u->save();
        $this->actingAs($u)->get('/')->assertRedirect('/login');
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'bad']);
        }
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'correct-horse-1'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $this->actingAs($this->user())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_change_own_password(): void
    {
        $u = $this->user();
        $this->actingAs($u)->put('/account/password', ['current_password' => 'wrong', 'password' => 'new-password-99', 'password_confirmation' => 'new-password-99'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($u)->put('/account/password', ['current_password' => 'correct-horse-1', 'password' => 'new-password-99', 'password_confirmation' => 'new-password-99'])
            ->assertSessionHas('ok');
        $this->assertTrue(\Hash::check('new-password-99', $u->fresh()->password));
    }

    public function test_bootstrap_admin_command(): void
    {
        putenv('ADMIN_EMAIL="Boss@Example.com"');
        putenv('ADMIN_PASSWORD=long-enough-pass');
        putenv('ADMIN_NAME=المدير العام');
        $this->artisan('markaz:bootstrap-admin')->assertSuccessful();
        $u = User::first();
        $this->assertSame('boss@example.com', $u->email);
        $this->assertTrue($u->is_admin);
        // تشغيل ثانٍ لا يغيّر شيئًا
        putenv('ADMIN_PASSWORD=another-long-pass');
        $this->artisan('markaz:bootstrap-admin')->assertSuccessful();
        $this->assertTrue(\Hash::check('long-enough-pass', $u->fresh()->password));
        // إعادة التعيين الصريحة
        putenv('ADMIN_RESET=true');
        $this->artisan('markaz:bootstrap-admin')->assertSuccessful();
        $this->assertTrue(\Hash::check('another-long-pass', $u->fresh()->password));
        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        putenv('ADMIN_NAME');
        putenv('ADMIN_RESET');
    }
}

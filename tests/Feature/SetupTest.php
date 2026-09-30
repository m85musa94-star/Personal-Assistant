<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = ['name' => 'أبو موسى', 'email' => 'Owner@Example.com', 'password' => 'long-enough-pass', 'password_confirmation' => 'long-enough-pass'];

    public function test_login_page_offers_setup_when_no_users(): void
    {
        $this->get('/login')->assertOk()->assertSee('أنشئ حساب المدير الأول');
    }

    public function test_first_admin_is_created_and_logged_in(): void
    {
        $this->get('/setup')->assertOk();
        $this->post('/setup', $this->payload)->assertRedirect('/');
        $u = User::first();
        $this->assertSame('owner@example.com', $u->email);
        $this->assertTrue($u->is_admin);
        $this->assertAuthenticatedAs($u);
    }

    public function test_setup_is_closed_once_a_user_exists(): void
    {
        $this->post('/setup', $this->payload)->assertRedirect('/');
        auth()->logout();
        $this->get('/setup')->assertNotFound();
        $this->post('/setup', ['name' => 'مهاجم', 'email' => 'evil@example.com', 'password' => 'long-enough-pass', 'password_confirmation' => 'long-enough-pass'])->assertNotFound();
        $this->assertSame(1, User::count());
        $this->get('/login')->assertDontSee('أنشئ حساب المدير الأول');
    }

    public function test_setup_validation(): void
    {
        $this->post('/setup', ['name' => 'x', 'email' => 'bad', 'password' => 'short', 'password_confirmation' => 'other'])
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertSame(0, User::count());
    }
}

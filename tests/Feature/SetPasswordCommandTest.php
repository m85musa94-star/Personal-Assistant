<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin_then_resets_password(): void
    {
        $this->artisan('markaz:set-password', ['email' => 'A@B.com', 'password' => 'first-long-password'])->assertSuccessful();
        $u = User::first();
        $this->assertTrue($u->is_admin);
        $this->artisan('markaz:set-password', ['email' => 'a@b.com', 'password' => 'second-long-password'])->assertSuccessful();
        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('second-long-password', $u->fresh()->password));
    }

    public function test_rejects_short_password(): void
    {
        $this->artisan('markaz:set-password', ['email' => 'a@b.com', 'password' => 'short'])->assertFailed();
        $this->assertSame(0, User::count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MakesData;
use Tests\TestCase;

class UsersAdminTest extends TestCase
{
    use MakesData, RefreshDatabase;

    public function test_non_admin_is_forbidden_everywhere(): void
    {
        $u = $this->user('u@example.com', 'hr');
        $other = $this->user('o@example.com');
        $this->actingAs($u)->get('/users')->assertForbidden();
        $this->actingAs($u)->get('/users/create')->assertForbidden();
        $this->actingAs($u)->get("/users/{$other->id}")->assertForbidden();
        $this->actingAs($u)->post('/users', ['name' => 'x', 'email' => 'x@example.com', 'password' => 'long-enough-pass', 'company_scope' => 'all'])->assertForbidden();
        $this->actingAs($u)->put("/users/{$other->id}", ['name' => 'x', 'email' => 'x@example.com'])->assertForbidden();
        $this->actingAs($u)->post("/users/{$other->id}/toggle")->assertForbidden();
        $this->actingAs($u)->put("/users/{$other->id}/password", ['password' => 'long-enough-pass'])->assertForbidden();
    }

    public function test_admin_sees_list_and_forms(): void
    {
        $a = $this->user('a@example.com', 'admin');
        $this->user('v@example.com');
        $this->actingAs($a)->get('/users')->assertOk()->assertSee('v@example.com')->assertSee('مشاهد (عرض فقط)');
        $this->actingAs($a)->get('/users/create')->assertOk()->assertSee('permissions[]', false)->assertSee('company_scope', false);
    }

    public function test_admin_creates_user_with_custom_permissions_who_can_login(): void
    {
        $a = $this->user('a@example.com', 'admin');
        $this->actingAs($a)->post('/users', ['name' => 'سارة', 'email' => 'Sara@Example.com', 'password' => 'long-enough-pass', 'permissions' => ['vehicles.edit', 'alerts.view'], 'company_scope' => 'all'])->assertRedirect();
        $sara = User::where('email', 'sara@example.com')->first();
        $this->assertNotNull($sara);
        $this->assertFalse($sara->is_admin);
        // التعديل يستلزم العرض
        $this->assertEqualsCanonicalizing(['vehicles.edit', 'vehicles.view', 'alerts.view'], $sara->permissions);
        auth()->logout();
        $this->post('/login', ['email' => 'sara@example.com', 'password' => 'long-enough-pass'])->assertRedirect('/');
    }

    public function test_creation_validation(): void
    {
        $a = $this->user('a@example.com', 'admin');
        $base = ['name' => 'x', 'email' => 'new@example.com', 'password' => 'long-enough-pass', 'company_scope' => 'all'];
        $this->actingAs($a)->post('/users', array_merge($base, ['email' => 'A@example.com']))->assertSessionHasErrors('email');
        $this->actingAs($a)->post('/users', array_merge($base, ['password' => 'short']))->assertSessionHasErrors('password');
        $this->actingAs($a)->post('/users', array_merge($base, ['permissions' => ['hack.everything']]))->assertSessionHasErrors('permissions.0');
        $this->actingAs($a)->post('/users', array_merge($base, ['company_scope' => 'selected']))->assertSessionHasErrors('company_ids');
        $this->actingAs($a)->post('/users', array_merge($base, ['company_scope' => 'selected', 'company_ids' => [9999]]))->assertSessionHasErrors('company_ids.0');
        $this->assertSame(1, User::count());
    }

    public function test_company_restriction_is_saved_and_admin_cannot_be_restricted(): void
    {
        $a = $this->user('a@example.com', 'admin');
        $c1 = $this->company('الأولى');
        $c2 = $this->company('الثانية');
        $this->actingAs($a)->post('/users', ['name' => 'مقيّد', 'email' => 'r@example.com', 'password' => 'long-enough-pass', 'permissions' => ['employees.view'], 'company_scope' => 'selected', 'company_ids' => [$c1->id]])->assertRedirect();
        $r = User::where('email', 'r@example.com')->first();
        $this->assertFalse($r->all_companies);
        $this->assertSame([$c1->id], $r->allowedCompanyIds());
        // تحويله لكل الشركات يمسح القيد
        $this->actingAs($a)->put("/users/{$r->id}", ['name' => 'مقيّد', 'email' => 'r@example.com', 'permissions' => ['employees.view'], 'company_scope' => 'all'])->assertRedirect();
        $this->assertNull($r->fresh()->allowedCompanyIds());
        $this->assertSame(0, $r->companies()->count());
        // مدير النظام بلا قيد حتى لو أُرسلت شركات
        $this->actingAs($a)->post('/users', ['name' => 'مدير2', 'email' => 'a2@example.com', 'password' => 'long-enough-pass', 'is_admin' => '1', 'company_scope' => 'selected', 'company_ids' => [$c2->id]])->assertRedirect();
        $a2 = User::where('email', 'a2@example.com')->first();
        $this->assertTrue($a2->is_admin);
        $this->assertNull($a2->permissions);
        $this->assertNull($a2->allowedCompanyIds());
    }

    public function test_update_permissions_and_cannot_edit_own_access(): void
    {
        $a = $this->user('a@example.com', 'admin');
        $u = $this->user('u@example.com');
        $this->actingAs($a)->put("/users/{$u->id}", ['name' => 'مشاهد', 'email' => 'u@example.com', 'permissions' => ['tasks.use', 'leaves.edit'], 'company_scope' => 'all'])->assertRedirect();
        $this->assertEqualsCanonicalizing(['tasks.use', 'leaves.edit', 'leaves.view', 'employees.view'], $u->fresh()->permissions);
        // أعلى من ذلك: ترقيته مديرًا
        $this->actingAs($a)->put("/users/{$u->id}", ['name' => 'مشاهد', 'email' => 'u@example.com', 'is_admin' => '1', 'company_scope' => 'all'])->assertRedirect();
        $this->assertTrue($u->fresh()->is_admin);
        // المدير لا يخفّض نفسه: حقوقه تُتجاهل، والاسم والبريد يُحدَّثان
        $this->actingAs($a)->put("/users/{$a->id}", ['name' => 'اسم جديد', 'email' => 'a@example.com', 'permissions' => [], 'company_scope' => 'selected'])->assertRedirect();
        $this->assertTrue($a->fresh()->is_admin);
        $this->assertSame('اسم جديد', $a->fresh()->name);
        $this->assertNull($a->fresh()->allowedCompanyIds());
    }

    public function test_toggle_and_password(): void
    {
        $a = $this->user('a@example.com', 'admin');
        $u = $this->user('u@example.com');
        $this->actingAs($a)->post("/users/{$u->id}/toggle")->assertSessionHas('ok');
        $this->assertFalse($u->fresh()->is_active);
        $this->actingAs($a)->post("/users/{$a->id}/toggle")->assertSessionHasErrors('users');
        $this->assertTrue($a->fresh()->is_active);
        $this->actingAs($a)->put("/users/{$u->id}/password", ['password' => 'brand-new-password'])->assertSessionHas('ok');
        $this->assertTrue(Hash::check('brand-new-password', $u->fresh()->password));
        $this->actingAs($a)->put("/users/{$u->id}/password", ['password' => 'short'])->assertSessionHasErrors('password');
    }

    public function test_permission_normalization_and_presets(): void
    {
        $this->assertEqualsCanonicalizing(['employees.view', 'employees.edit'], Permissions::normalize(['employees.edit']));
        $this->assertEqualsCanonicalizing(['employees.view', 'records.edit'], Permissions::normalize(['records.edit', 'ghost']));
        foreach (Permissions::PRESETS as $name => $list) {
            $this->assertSame($name, Permissions::presetOf($list), $name);
            $this->assertSame([], array_diff($list, Permissions::all()), "preset {$name} uses unknown permission");
        }
        $this->assertSame('custom', Permissions::presetOf(['alerts.view']));
    }

    public function test_migration_preserved_roles_semantics(): void
    {
        // المدير: بلا قائمة (كل شيء)
        $a = $this->user('a@example.com', 'admin');
        $this->assertTrue($a->hasPermission('anything.at.all'));
        $m = $this->user('m@example.com', 'hr');
        $this->assertTrue($m->hasPermission('vehicles.edit'));
        $this->assertFalse($m->hasPermission('users.manage'));
        $v = $this->user('v@example.com');
        $this->assertTrue($v->hasPermission('employees.view'));
        $this->assertFalse($v->hasPermission('employees.edit'));
    }
}

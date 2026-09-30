<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesData;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use MakesData, RefreshDatabase;

    public function test_guest_redirected(): void
    {
        foreach (['/', '/employees', '/vehicles', '/leaves', '/alerts', '/vehicle-records', '/settings/companies', '/tasks'] as $p) {
            $this->get($p)->assertRedirect('/login');
        }
        $this->post('/company/switch', ['company' => 'all'])->assertRedirect('/login');
    }

    public function test_viewer_can_read_but_not_write(): void
    {
        $viewer = $this->user();
        $c = $this->company();
        $this->actingAs($viewer)->get('/settings/companies')->assertOk();
        $this->actingAs($viewer)->get("/settings/companies/{$c->id}")->assertOk();
        $this->actingAs($viewer)->get('/settings/companies/create')->assertForbidden();
        $this->actingAs($viewer)->post('/settings/companies', ['name' => 'x'])->assertForbidden();
        $this->actingAs($viewer)->put("/settings/companies/{$c->id}", ['name' => 'y'])->assertForbidden();
        $this->actingAs($viewer)->delete("/settings/companies/{$c->id}")->assertForbidden();
        $this->actingAs($viewer)->post('/employees', ['company_id' => $c->id, 'name' => 'x', 'status' => 'active'])->assertForbidden();
        $this->actingAs($viewer)->post('/vehicles', ['company_id' => $c->id, 'plate' => 'X', 'status' => 'active'])->assertForbidden();
        $this->assertSame(1, Company::count());
    }

    public function test_manager_creates_and_edits_company(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $this->actingAs($m)->post('/settings/companies', ['name' => 'مؤسسة النور', 'name_en' => 'Alnoor', 'cr_number' => '1010', 'is_active' => '1'])->assertRedirect();
        $c = Company::first();
        $this->assertTrue($c->is_active);
        $this->actingAs($m)->put("/settings/companies/{$c->id}", ['name' => 'مؤسسة النور', 'name_en' => '', 'cr_number' => '1010'])->assertRedirect();
        $this->assertNull($c->fresh()->name_en);
        $this->assertFalse($c->fresh()->is_active, 'checkbox غير مُرسل = معطّلة');
    }

    public function test_company_delete_rules(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $admin = $this->user('a@example.com', 'admin');
        $c = $this->company();
        $this->emp($c);
        $this->actingAs($hr)->delete("/settings/companies/{$c->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/settings/companies/{$c->id}")->assertSessionHas('warn');
        $this->assertNotNull($c->fresh());
        $empty = $this->company('فارغة');
        $this->actingAs($admin)->delete("/settings/companies/{$empty->id}")->assertSessionHas('ok');
        $this->assertNull($empty->fresh());
    }

    public function test_company_switcher_scopes_lists_and_persists(): void
    {
        $u = $this->user();
        $a = $this->company('الشركة الأولى');
        $b = $this->company('الشركة الثانية');
        $this->emp($a, 'موظف-أ');
        $this->emp($b, 'موظف-ب');
        $this->car($a, 'AAA 111');
        $this->car($b, 'BBB 222');

        // الكل افتراضيًا
        $this->actingAs($u)->get('/employees')->assertSee('موظف-أ')->assertSee('موظف-ب');
        $this->actingAs($u)->post('/company/switch', ['company' => $a->id])->assertRedirect();
        $this->assertSame($a->id, $u->fresh()->last_company_id);
        $this->actingAs($u->fresh())->get('/employees')->assertSee('موظف-أ')->assertDontSee('موظف-ب');
        $this->actingAs($u->fresh())->get('/vehicles')->assertSee('AAA 111')->assertDontSee('BBB 222');
        $this->actingAs($u->fresh())->post('/company/switch', ['company' => $b->id]);
        $this->actingAs($u->fresh())->get('/employees')->assertSee('موظف-ب')->assertDontSee('موظف-أ');
        $this->actingAs($u->fresh())->post('/company/switch', ['company' => 'all']);
        $this->assertNull($u->fresh()->last_company_id);
        $this->actingAs($u->fresh())->get('/employees')->assertSee('موظف-أ')->assertSee('موظف-ب');
        $this->actingAs($u)->post('/company/switch', ['company' => 99999])->assertStatus(422);
    }

    public function test_creating_without_company_redirects_to_add_one(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $this->actingAs($m)->get('/employees/create')->assertRedirect('/settings/companies/create');
        $this->actingAs($m)->get('/vehicles/create')->assertRedirect('/settings/companies/create');
    }

    public function test_home_shows_apps_and_counts(): void
    {
        $u = $this->user();
        $c = $this->company();
        $this->emp($c);
        $this->car($c);
        $this->actingAs($u)->get('/')->assertOk()->assertSee('الموظفون')->assertSee('السيارات')->assertSee('المهام والجدولة')->assertSee('التنبيهات');
    }
}

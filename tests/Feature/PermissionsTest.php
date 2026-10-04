<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeRecord;
use App\Models\Vehicle;
use App\Models\VehicleRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesData;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use MakesData, RefreshDatabase;

    public function test_tasks_only_user_sees_only_tasks(): void
    {
        $u = $this->user('t@example.com', 'user', ['tasks.use']);
        $c = $this->company();
        $e = $this->emp($c);
        $v = $this->car($c);
        $this->actingAs($u)->get('/tasks')->assertOk();
        $this->actingAs($u)->getJson('/api/state')->assertOk();
        foreach (['/employees', "/employees/{$e->id}", '/employees/register', '/employees/export', "/employees/{$e->id}/print", '/leaves', '/vehicles', "/vehicles/{$v->id}", '/vehicle-records', '/alerts', '/settings/companies', '/settings/leave-types'] as $p) {
            $this->actingAs($u)->get($p)->assertForbidden();
        }
        $home = $this->actingAs($u)->get('/')->assertOk();
        $home->assertSee('المهام والجدولة')->assertDontSee('route-employees', false);
        $this->assertStringNotContainsString(route('employees.index'), $home->getContent());
        $this->assertStringNotContainsString(route('vehicles.index'), $home->getContent());
        $this->assertStringNotContainsString(route('alerts.index'), $home->getContent());
    }

    public function test_no_tasks_permission_blocks_tasks_app(): void
    {
        $u = $this->user('n@example.com', 'user', ['employees.view']);
        $this->actingAs($u)->get('/tasks')->assertForbidden();
        $this->actingAs($u)->getJson('/api/state')->assertForbidden();
        $this->actingAs($u)->putJson('/api/state', ['data' => [], 'ts' => 1])->assertForbidden();
    }

    public function test_view_vs_edit_per_module(): void
    {
        $hr = $this->user('hr@example.com', 'user', ['tasks.use', 'employees.edit', 'leaves.edit']);
        $fleet = $this->user('f@example.com', 'user', ['vehicles.edit']);
        $c = $this->company();
        $e = $this->emp($c);
        $v = $this->car($c);

        // موظفون: يعدّل
        $this->actingAs($hr)->put("/employees/{$e->id}", ['company_id' => $c->id, 'name' => 'جديد', 'status' => 'active'])->assertRedirect();
        $this->assertSame('جديد', $e->fresh()->name);
        // مركبات: لا يرى ولا يعدّل
        $this->actingAs($hr)->get('/vehicles')->assertForbidden();
        $this->actingAs($hr)->put("/vehicles/{$v->id}", ['company_id' => $c->id, 'plate' => 'X', 'status' => 'active'])->assertForbidden();
        $this->actingAs($hr)->post("/vehicles/{$v->id}/records", ['type' => 'fuel', 'record_date' => '2026-01-01'])->assertForbidden();

        // مسؤول سيارات: يعدّل المركبات ولا يمسّ الموظفين
        $this->actingAs($fleet)->put("/vehicles/{$v->id}", ['company_id' => $c->id, 'plate' => 'NEW 1', 'status' => 'active'])->assertRedirect();
        $this->assertSame('NEW 1', $v->fresh()->plate);
        $this->actingAs($fleet)->get('/employees')->assertForbidden();
        $this->actingAs($fleet)->post('/employees', ['company_id' => $c->id, 'name' => 'x', 'status' => 'active'])->assertForbidden();
        $this->actingAs($fleet)->get('/leaves')->assertForbidden();
    }

    public function test_viewer_can_view_but_every_write_is_forbidden(): void
    {
        $v = $this->user('v@example.com');
        $c = $this->company();
        $e = $this->emp($c);
        $car = $this->car($c);
        $doc = $e->documents()->create(['type' => 'iqama']);
        $leave = $e->leaves()->create(['leave_type_id' => $this->annual()->id, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2, 'status' => 'approved']);
        foreach (['/employees', "/employees/{$e->id}", '/leaves', '/vehicles', "/vehicles/{$car->id}", '/alerts', '/vehicle-records', '/settings/companies', '/settings/leave-types', '/employees/register'] as $p) {
            $this->actingAs($v)->get($p)->assertOk();
        }
        $this->actingAs($v)->get('/employees/create')->assertForbidden();
        $this->actingAs($v)->post('/employees', ['company_id' => $c->id, 'name' => 'x', 'status' => 'active'])->assertForbidden();
        $this->actingAs($v)->put("/employees/{$e->id}", ['company_id' => $c->id, 'name' => 'x', 'status' => 'active'])->assertForbidden();
        $this->actingAs($v)->post("/employees/{$e->id}/documents", ['type' => 'iqama'])->assertForbidden();
        $this->actingAs($v)->put("/employee-documents/{$doc->id}", ['type' => 'iqama'])->assertForbidden();
        $this->actingAs($v)->delete("/employee-documents/{$doc->id}")->assertForbidden();
        $this->actingAs($v)->post("/employees/{$e->id}/records", ['type' => 'note', 'title' => 'x', 'event_date' => '2026-01-01'])->assertForbidden();
        $this->actingAs($v)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => 1, 'start_date' => '2031-01-01', 'end_date' => '2031-01-02'])->assertForbidden();
        $this->actingAs($v)->post("/leaves/{$leave->id}/toggle")->assertForbidden();
        $this->actingAs($v)->get('/settings/companies/create')->assertForbidden();
        $this->actingAs($v)->put("/settings/companies/{$c->id}", ['name' => 'x'])->assertForbidden();
        $this->actingAs($v)->post('/settings/leave-types', ['name' => 'x'])->assertForbidden();
        $this->actingAs($v)->delete("/employees/{$e->id}")->assertForbidden();
        $this->actingAs($v)->delete("/vehicles/{$car->id}")->assertForbidden();
        $this->assertSame(1, Employee::count());
        $this->assertSame(1, Vehicle::count());
    }

    public function test_records_edit_is_independent_of_employee_edit(): void
    {
        $u = $this->user('r@example.com', 'user', ['employees.view', 'records.edit']);
        $e = $this->emp($this->company());
        $this->actingAs($u)->post("/employees/{$e->id}/records", ['type' => 'warning', 'title' => 'تأخر', 'event_date' => '2026-02-01'])->assertRedirect();
        $this->assertSame(1, $e->records()->where('type', 'warning')->count());
        $this->actingAs($u)->put("/employees/{$e->id}", ['company_id' => $e->company_id, 'name' => 'x', 'status' => 'active'])->assertForbidden();
    }

    public function test_settings_edit_permissions(): void
    {
        $co = $this->user('c@example.com', 'user', ['companies.edit']);
        $lt = $this->user('l@example.com', 'user', ['leave_types.edit', 'leaves.view']);
        $c = $this->company();
        $this->actingAs($co)->post('/settings/companies', ['name' => 'جديدة', 'is_active' => '1'])->assertRedirect();
        $this->actingAs($co)->post('/settings/leave-types', ['name' => 'x'])->assertForbidden();
        $this->actingAs($lt)->post('/settings/leave-types', ['name' => 'إجازة جديدة'])->assertSessionHas('ok');
        $this->actingAs($lt)->put("/settings/companies/{$c->id}", ['name' => 'x'])->assertForbidden();
    }

    public function test_inactive_user_is_blocked_everywhere(): void
    {
        $u = $this->user('x@example.com', 'hr');
        $u->update(['is_active' => false]);
        $this->actingAs($u)->get('/employees')->assertRedirect('/login');
        $this->actingAs($u)->get('/')->assertRedirect('/login');
    }

    // ---------- تقييد الشركات ----------

    private function restricted(array $perms, array $companyIds, string $email = 'r@example.com')
    {
        $u = $this->user($email, 'user', $perms);
        $u->update(['all_companies' => false]);
        $u->companies()->sync($companyIds);

        return $u->fresh();
    }

    public function test_restricted_user_sees_only_allowed_companies_everywhere(): void
    {
        $a = $this->company('شركة-ألف');
        $b = $this->company('شركة-باء');
        $ea = $this->emp($a, 'موظف-ألف');
        $eb = $this->emp($b, 'موظف-باء');
        $va = $this->car($a, 'AAA 111');
        $vb = $this->car($b, 'BBB 222');
        $ea->documents()->create(['type' => 'iqama', 'number' => 'IQ-A', 'expiry_date' => today()->subDay()]);
        $eb->documents()->create(['type' => 'iqama', 'number' => 'IQ-B', 'expiry_date' => today()->subDay()]);
        $ea->leaves()->create(['leave_type_id' => 1, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2, 'status' => 'approved', 'reason' => 'سبب-ألف']);
        $eb->leaves()->create(['leave_type_id' => 1, 'start_date' => '2030-02-01', 'end_date' => '2030-02-02', 'days' => 2, 'status' => 'approved', 'reason' => 'سبب-باء']);
        $va->records()->create(['type' => 'fuel', 'record_date' => '2030-01-01', 'description' => 'سجل-ألف']);
        $vb->records()->create(['type' => 'fuel', 'record_date' => '2030-01-01', 'description' => 'سجل-باء']);
        $u = $this->restricted(['tasks.use', 'employees.view', 'leaves.view', 'vehicles.view', 'alerts.view'], [$a->id]);

        $this->actingAs($u)->get('/employees')->assertSee('موظف-ألف')->assertDontSee('موظف-باء');
        $this->actingAs($u)->get('/employees?status=all&view=list')->assertSee('موظف-ألف')->assertDontSee('موظف-باء');
        $this->actingAs($u)->get('/vehicles')->assertSee('AAA 111')->assertDontSee('BBB 222');
        $this->actingAs($u)->get('/leaves')->assertSee('سبب-ألف')->assertDontSee('سبب-باء');
        $this->actingAs($u)->get('/vehicle-records')->assertSee('سجل-ألف')->assertDontSee('سجل-باء');
        $this->actingAs($u)->get('/alerts')->assertSee('IQ-A')->assertDontSee('IQ-B');
        $this->actingAs($u)->get('/settings/companies')->assertSee('شركة-ألف')->assertDontSee('شركة-باء');
        $this->actingAs($u)->get('/employees/register')->assertSee('موظف-ألف')->assertDontSee('موظف-باء');
        $csv = $this->actingAs($u)->get('/employees/export')->streamedContent();
        $this->assertStringContainsString('موظف-ألف', $csv);
        $this->assertStringNotContainsString('موظف-باء', $csv);
        $home = $this->actingAs($u)->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('شركة-باء', $home);
    }

    public function test_restricted_user_cannot_reach_other_company_records_by_url(): void
    {
        $a = $this->company('A');
        $b = $this->company('B');
        $eb = $this->emp($b, 'غريب');
        $vb = $this->car($b, 'BBB 222');
        $doc = $eb->documents()->create(['type' => 'iqama']);
        $vdoc = $vb->documents()->create(['type' => 'registration']);
        $leave = $eb->leaves()->create(['leave_type_id' => 1, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2, 'status' => 'approved']);
        $rec = VehicleRecord::create(['vehicle_id' => $vb->id, 'type' => 'fuel', 'record_date' => today()]);
        $erec = EmployeeRecord::create(['employee_id' => $eb->id, 'type' => 'note', 'title' => 'x', 'event_date' => today()]);
        $u = $this->restricted(['employees.view', 'employees.edit', 'records.edit', 'leaves.view', 'leaves.edit', 'vehicles.view', 'vehicles.edit', 'companies.edit', 'alerts.view'], [$a->id]);

        $this->actingAs($u)->get("/employees/{$eb->id}")->assertNotFound();
        $this->actingAs($u)->get("/employees/{$eb->id}/print")->assertNotFound();
        $this->actingAs($u)->put("/employees/{$eb->id}", ['company_id' => $a->id, 'name' => 'اختطاف', 'status' => 'active'])->assertNotFound();
        $this->actingAs($u)->post("/employees/{$eb->id}/documents", ['type' => 'iqama'])->assertNotFound();
        $this->actingAs($u)->put("/employee-documents/{$doc->id}", ['type' => 'iqama'])->assertNotFound();
        $this->actingAs($u)->delete("/employee-documents/{$doc->id}")->assertNotFound();
        $this->actingAs($u)->post("/employees/{$eb->id}/records", ['type' => 'note', 'title' => 'x', 'event_date' => '2026-01-01'])->assertNotFound();
        $this->actingAs($u)->delete("/employee-records/{$erec->id}")->assertNotFound();
        $this->actingAs($u)->post("/leaves/{$leave->id}/toggle")->assertNotFound();
        $this->actingAs($u)->post('/leaves', ['employee_id' => $eb->id, 'leave_type_id' => 1, 'start_date' => '2031-01-01', 'end_date' => '2031-01-02'])->assertNotFound();
        $this->actingAs($u)->get("/vehicles/{$vb->id}")->assertNotFound();
        $this->actingAs($u)->put("/vehicles/{$vb->id}", ['company_id' => $a->id, 'plate' => 'X', 'status' => 'active'])->assertNotFound();
        $this->actingAs($u)->put("/vehicles/{$vb->id}/status", ['status' => 'sold'])->assertNotFound();
        $this->actingAs($u)->post("/vehicles/{$vb->id}/documents", ['type' => 'registration'])->assertNotFound();
        $this->actingAs($u)->put("/vehicle-documents/{$vdoc->id}", ['type' => 'registration'])->assertNotFound();
        $this->actingAs($u)->post("/vehicles/{$vb->id}/records", ['type' => 'fuel', 'record_date' => '2026-01-01'])->assertNotFound();
        $this->actingAs($u)->delete("/vehicle-records/{$rec->id}")->assertNotFound();
        $this->actingAs($u)->get("/settings/companies/{$b->id}")->assertNotFound();
        $this->actingAs($u)->put("/settings/companies/{$b->id}", ['name' => 'اختطاف'])->assertNotFound();
        $this->assertSame('غريب', $eb->fresh()->name);
        $this->assertSame('BBB 222', $vb->fresh()->plate);
        $this->assertSame('B', $b->fresh()->name);
    }

    public function test_restricted_user_cannot_create_in_other_company_or_switch_to_it(): void
    {
        $a = $this->company('A');
        $b = $this->company('B');
        $u = $this->restricted(['employees.edit', 'vehicles.edit', 'tasks.use'], [$a->id]);
        $this->actingAs($u)->post('/employees', ['company_id' => $b->id, 'name' => 'x', 'status' => 'active'])->assertSessionHasErrors('company_id');
        $this->actingAs($u)->post('/vehicles', ['company_id' => $b->id, 'plate' => 'Z', 'status' => 'active'])->assertSessionHasErrors('company_id');
        $this->actingAs($u)->post('/employees', ['company_id' => $a->id, 'name' => 'مسموح', 'status' => 'active'])->assertSessionHasNoErrors();
        $this->actingAs($u)->post('/company/switch', ['company' => $b->id])->assertForbidden();
        $this->actingAs($u)->post('/company/switch', ['company' => $a->id])->assertRedirect();
        $this->assertSame(1, Employee::count());
        // الصفحة الرئيسية لا تعرض شركة أخرى في المبدّل
        $this->actingAs($u->fresh())->get('/employees/create')->assertOk()->assertDontSee('>B<', false);
    }

    public function test_stale_last_company_outside_scope_is_ignored(): void
    {
        $a = $this->company('A');
        $b = $this->company('B');
        $this->emp($a, 'موظف-أ');
        $this->emp($b, 'موظف-ب');
        $u = $this->restricted(['employees.view'], [$a->id]);
        $u->forceFill(['last_company_id' => $b->id])->save(); // كان لها صلاحية ثم سُحبت
        $this->actingAs($u->fresh())->get('/employees')->assertSee('موظف-أ')->assertDontSee('موظف-ب');
    }

    public function test_restricted_with_no_companies_sees_nothing(): void
    {
        $a = $this->company('A');
        $this->emp($a, 'موظف-أ');
        $u = $this->restricted(['employees.view', 'vehicles.view'], []);
        $this->actingAs($u)->get('/employees')->assertOk()->assertDontSee('موظف-أ');
    }
}

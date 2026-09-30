<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesData;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use MakesData, RefreshDatabase;

    public function test_create_update_and_unique_code(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $c = $this->company();
        $this->actingAs($m)->get('/employees/create')->assertOk();
        $this->actingAs($m)->post('/employees', ['company_id' => $c->id, 'name' => 'سارة', 'name_en' => 'Sara', 'code' => 'E1', 'nationality' => 'سعودية', 'status' => 'active', 'hire_date' => '2025-03-01'])->assertRedirect();
        $e = Employee::first();
        $this->assertSame('E1', $e->code);
        $this->actingAs($m)->post('/employees', ['company_id' => $c->id, 'name' => 'آخر', 'code' => 'E1', 'status' => 'active'])->assertSessionHasErrors('code');
        $this->actingAs($m)->put("/employees/{$e->id}", ['company_id' => $c->id, 'name' => 'سارة أحمد', 'name_en' => '', 'code' => 'E1', 'status' => 'inactive'])->assertRedirect();
        $this->assertSame('inactive', $e->fresh()->status);
        $this->assertNull($e->fresh()->name_en);
        $this->assertSame('سارة أحمد', $e->fresh()->name);
    }

    public function test_update_without_notes_field_keeps_existing_notes(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $c = $this->company();
        $e = $this->emp($c, 'x', ['notes' => 'ملاحظة مهمة']);
        $this->actingAs($m)->put("/employees/{$e->id}", ['company_id' => $c->id, 'name' => 'x', 'status' => 'active'])->assertRedirect();
        $this->assertSame('ملاحظة مهمة', $e->fresh()->notes);
    }

    public function test_viewer_cannot_edit_but_can_view(): void
    {
        $v = $this->user();
        $c = $this->company();
        $e = $this->emp($c, 'خالد');
        $this->actingAs($v)->get("/employees/{$e->id}")->assertOk()->assertSee('خالد')->assertDontSee('إضافة وثيقة');
        $this->actingAs($v)->put("/employees/{$e->id}", ['company_id' => $c->id, 'name' => 'x', 'status' => 'active'])->assertForbidden();
        $this->actingAs($v)->post("/employees/{$e->id}/documents", ['type' => 'iqama'])->assertForbidden();
        $this->actingAs($v)->delete("/employees/{$e->id}")->assertForbidden();
    }

    public function test_only_admin_deletes_employee(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $admin = $this->user('a@example.com', 'admin');
        $e = $this->emp($this->company());
        $this->actingAs($hr)->delete("/employees/{$e->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/employees/{$e->id}")->assertRedirect('/employees');
        $this->assertNull($e->fresh());
    }

    public function test_documents_lifecycle_iqama_and_insurance(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $e = $this->emp($this->company());
        $this->actingAs($m)->post("/employees/{$e->id}/documents", ['type' => 'iqama', 'number' => '2123456789', 'issue_date' => '2025-01-01', 'expiry_date' => '2026-01-01'])->assertRedirect();
        $this->actingAs($m)->post("/employees/{$e->id}/documents", ['type' => 'insurance', 'provider' => 'بوبا', 'number' => 'POL-1', 'expiry_date' => today()->addDays(20)->toDateString()])->assertRedirect();
        $this->assertSame(2, $e->documents()->count());
        $ins = $e->documents()->where('type', 'insurance')->first();
        $this->assertSame('amb', $ins->tone());
        $this->assertSame('بوبا', $ins->provider);

        // تجديد: تعديل تاريخ الانتهاء
        $this->actingAs($m)->put("/employee-documents/{$ins->id}", ['type' => 'insurance', 'provider' => 'بوبا', 'number' => 'POL-2', 'expiry_date' => today()->addYear()->toDateString()])->assertRedirect();
        $this->assertSame('', $ins->fresh()->tone());
        $this->assertSame('POL-2', $ins->fresh()->number);

        // تحقق
        $this->actingAs($m)->post("/employees/{$e->id}/documents", ['type' => 'bogus'])->assertSessionHasErrors('type');
        $this->actingAs($m)->post("/employees/{$e->id}/documents", ['type' => 'iqama', 'issue_date' => '2025-05-01', 'expiry_date' => '2025-01-01'])->assertSessionHasErrors('expiry_date');

        $this->actingAs($m)->get("/employees/{$e->id}?tab=documents")->assertOk()->assertSee('2123456789')->assertSee('POL-2');
        $this->actingAs($m)->get("/employees/{$e->id}?tab=documents&edit_doc={$ins->id}")->assertOk()->assertSee('POL-2');
        $doc = $e->documents()->where('type', 'iqama')->first();
        $this->actingAs($m)->delete("/employee-documents/{$doc->id}")->assertRedirect();
        $this->assertSame(1, $e->documents()->count());
    }

    public function test_tone_thresholds(): void
    {
        $d = new EmployeeDocument;
        foreach ([[-1, 'red'], [0, 'amb'], [30, 'amb'], [31, 'yel'], [60, 'yel'], [61, '']] as [$offset, $tone]) {
            $d->expiry_date = today()->addDays($offset);
            $this->assertSame($tone, $d->tone(), "offset {$offset}");
        }
        $d->expiry_date = null;
        $this->assertSame('', $d->tone());
        $this->assertNull($d->daysLeft());
    }

    public function test_list_search_filters_and_alert_filter(): void
    {
        $v = $this->user();
        $c = $this->company();
        $ok = $this->emp($c, 'سليم', ['nationality' => 'مصري']);
        $warn = $this->emp($c, 'منتهي', ['phone' => '0555']);
        $this->emp($c, 'غائب', ['status' => 'inactive']);
        $warn->documents()->create(['type' => 'iqama', 'expiry_date' => today()->subDay()]);
        $ok->documents()->create(['type' => 'iqama', 'expiry_date' => today()->addYear()]);
        $this->actingAs($v)->get('/employees')->assertSee('سليم')->assertSee('منتهي')->assertDontSee('غائب');
        $this->actingAs($v)->get('/employees?status=inactive')->assertSee('غائب')->assertDontSee('سليم');
        $this->actingAs($v)->get('/employees?status=all')->assertSee('غائب')->assertSee('سليم');
        $this->actingAs($v)->get('/employees?q=مصري')->assertSee('سليم')->assertDontSee('منتهي');
        $this->actingAs($v)->get('/employees?q=0555&view=list')->assertSee('منتهي')->assertDontSee('سليم');
        $this->actingAs($v)->get('/employees?alerts=1')->assertSee('منتهي')->assertDontSee('سليم');
    }

    public function test_on_leave_badge(): void
    {
        $v = $this->user();
        $e = $this->emp($this->company(), 'المجاز');
        $e->leaves()->create(['leave_type_id' => $this->annual()->id, 'start_date' => today()->subDay(), 'end_date' => today()->addDay(), 'days' => 3, 'status' => 'approved']);
        $this->assertTrue($e->onLeaveToday());
        $this->actingAs($v)->get('/employees')->assertSee('في إجازة');
    }
}

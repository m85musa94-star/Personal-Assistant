<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesData;
use Tests\TestCase;

class EmployeeRecordTest extends TestCase
{
    use MakesData, RefreshDatabase;

    private function events(Employee $e): array
    {
        return $e->records()->where('type', 'system')->orderBy('id')->pluck('event')->all();
    }

    public function test_create_and_update_are_logged_automatically(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $a = $this->company('شركة أ', ['name_en' => 'Company A']);
        $b = $this->company('شركة ب', ['name_en' => 'Company B']);
        $this->actingAs($m)->post('/employees', ['company_id' => $a->id, 'name' => 'خالد', 'status' => 'active', 'job_title' => 'محاسب'])->assertRedirect();
        $e = Employee::first();
        $this->assertSame(['created'], $this->events($e));
        $this->actingAs($m)->put("/employees/{$e->id}", ['company_id' => $b->id, 'name' => 'خالد', 'status' => 'inactive', 'job_title' => 'مدير مالي'])->assertRedirect();
        $this->assertSame(['created', 'company', 'status', 'job'], $this->events($e));
        // تعديل بلا تغيير جوهري لا يسجّل شيئًا
        $this->actingAs($m)->put("/employees/{$e->id}", ['company_id' => $b->id, 'name' => 'خالد أحمد', 'status' => 'inactive', 'job_title' => 'مدير مالي'])->assertRedirect();
        $this->assertCount(4, $e->records);
        $rec = $e->records()->where('event', 'company')->first();
        $this->assertSame($m->id, $rec->user_id);
        $this->assertStringContainsString('شركة أ', $rec->text());
        $this->assertStringContainsString('شركة ب', $rec->text());
    }

    public function test_saving_without_changing_company_does_not_log_a_move(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $c = $this->company();
        $e = $this->emp($c, 'x', ['job_title' => 'سائق']);
        // النموذج الحقيقي يرسل company_id كنص
        $this->actingAs($m)->put("/employees/{$e->id}", ['company_id' => (string) $c->id, 'name' => 'x', 'status' => 'active', 'job_title' => 'سائق'])->assertRedirect();
        $this->assertSame([], $this->events($e));
        $this->actingAs($m)->put("/employees/{$e->id}", ['company_id' => (string) $c->id, 'name' => 'x', 'status' => 'active', 'job_title' => 'مشرف'])->assertRedirect();
        $this->assertSame(['job'], $this->events($e));
    }

    public function test_event_text_follows_interface_language(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $a = $this->company('شركة أ', ['name_en' => 'Company A']);
        $e = $this->emp($a);
        EmployeeRecord::log($e, 'created', ['company' => $a->name, 'company_en' => $a->name_en], $m->id);
        EmployeeRecord::log($e, 'status', ['status' => 'inactive'], $m->id);
        $this->assertStringContainsString('شركة أ', $e->records()->where('event', 'created')->first()->text());
        app()->setLocale('en');
        $this->assertSame('Employee added to Company A', $e->records()->where('event', 'created')->first()->text());
        $this->assertSame('Status changed to: Inactive', $e->records()->where('event', 'status')->first()->text());
    }

    public function test_documents_and_leaves_are_logged(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $e = $this->emp($this->company());
        $this->actingAs($m)->post("/employees/{$e->id}/documents", ['type' => 'iqama', 'number' => '2123', 'expiry_date' => '2026-01-01']);
        $doc = $e->documents()->first();
        $this->actingAs($m)->put("/employee-documents/{$doc->id}", ['type' => 'iqama', 'number' => '2123', 'expiry_date' => '2027-01-01']); // تجديد
        $this->actingAs($m)->put("/employee-documents/{$doc->id}", ['type' => 'iqama', 'number' => '2124', 'expiry_date' => '2027-01-01']); // تعديل
        $this->actingAs($m)->delete("/employee-documents/{$doc->id}");
        $this->actingAs($m)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => $this->annual()->id, 'start_date' => '2030-03-01', 'end_date' => '2030-03-03']);
        $leave = $e->leaves()->first();
        $this->actingAs($m)->post("/leaves/{$leave->id}/toggle");
        $this->actingAs($m)->post("/leaves/{$leave->id}/toggle");
        $this->assertSame(['doc_added', 'doc_renewed', 'doc_edited', 'doc_deleted', 'leave_added', 'leave_cancelled', 'leave_restored'], $this->events($e));
        $renewed = $e->records()->where('event', 'doc_renewed')->first();
        $this->assertStringContainsString('2026-01-01', $renewed->text());
        $this->assertStringContainsString('2027-01-01', $renewed->text());
        $this->assertStringContainsString('الإقامة', $e->records()->where('event', 'doc_added')->first()->text());
        app()->setLocale('en');
        $this->assertStringContainsString('Iqama (residence)', $e->records()->where('event', 'doc_added')->first()->text());
        $this->assertStringContainsString('Annual leave', $e->records()->where('event', 'leave_added')->first()->text());
    }

    public function test_manual_entries_validation_and_delete_rules(): void
    {
        $author = $this->user('a@example.com', 'hr');
        $other = $this->user('o@example.com', 'hr');
        $admin = $this->user('admin@example.com', 'admin');
        $e = $this->emp($this->company());
        $ok = ['type' => 'warning', 'title' => 'تأخر متكرر', 'body' => 'تأخر ثلاث مرات', 'event_date' => '2026-03-01'];
        $this->actingAs($author)->post("/employees/{$e->id}/records", $ok)->assertRedirect();
        $entry = $e->records()->where('type', 'warning')->first();
        $this->assertSame($author->id, $entry->user_id);
        $this->actingAs($author)->post("/employees/{$e->id}/records", array_merge($ok, ['title' => '']))->assertSessionHasErrors('title');
        $this->actingAs($author)->post("/employees/{$e->id}/records", array_merge($ok, ['type' => 'system']))->assertSessionHasErrors('type');
        $this->actingAs($author)->post("/employees/{$e->id}/records", array_merge($ok, ['event_date' => 'ليس تاريخًا']))->assertSessionHasErrors('event_date');
        // الحذف: كاتب الإدخال أو المدير فقط
        $this->actingAs($other)->delete("/employee-records/{$entry->id}")->assertForbidden();
        $this->actingAs($author)->delete("/employee-records/{$entry->id}")->assertRedirect();
        $this->assertNull($entry->fresh());
        $again = EmployeeRecord::create(['employee_id' => $e->id, 'user_id' => $author->id, 'type' => 'note', 'title' => 'x', 'event_date' => today()]);
        $this->actingAs($admin)->delete("/employee-records/{$again->id}")->assertRedirect();
        // الأحداث التلقائية لا تُحذف حتى للمدير
        $sys = EmployeeRecord::log($e, 'created', ['company' => 'x'], $author->id);
        $this->actingAs($admin)->delete("/employee-records/{$sys->id}")->assertForbidden();
        $this->assertNotNull($sys->fresh());
    }

    public function test_record_tab_renders_timeline(): void
    {
        $v = $this->user('v@example.com');
        $e = $this->emp($this->company());
        EmployeeRecord::create(['employee_id' => $e->id, 'user_id' => null, 'type' => 'commendation', 'title' => 'تقدير على الإنجاز', 'body' => 'أنجز المشروع قبل الموعد', 'event_date' => '2026-02-01']);
        EmployeeRecord::log($e, 'created', ['company' => 'شركتنا']);
        $this->actingAs($v)->get("/employees/{$e->id}?tab=record")->assertOk()->assertSee('تقدير على الإنجاز')->assertSee('أنجز المشروع قبل الموعد')->assertSee('شكر وتقدير')->assertSee('شركتنا')->assertDontSee('إضافة إدخال إلى السجل');
        $m = $this->user('m@example.com', 'hr');
        $this->actingAs($m)->get("/employees/{$e->id}?tab=record")->assertSee('إضافة إدخال إلى السجل');
    }

    public function test_employee_file_print_page(): void
    {
        $v = $this->user('v@example.com');
        $e = $this->emp($this->company('شركة الطباعة'), 'موظف الطباعة', ['job_title' => 'سائق', 'nationality' => 'هندي']);
        $e->documents()->create(['type' => 'iqama', 'number' => 'IQ-PRINT', 'expiry_date' => today()->subDay()]);
        $e->leaves()->create(['leave_type_id' => 1, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2, 'status' => 'approved', 'reason' => 'ظرف عائلي']);
        EmployeeRecord::create(['employee_id' => $e->id, 'type' => 'note', 'title' => 'ملاحظة للطباعة', 'event_date' => today()]);
        $this->actingAs($v)->get("/employees/{$e->id}/print")->assertOk()->assertSee('موظف الطباعة')->assertSee('IQ-PRINT')->assertSee('ظرف عائلي')->assertSee('ملاحظة للطباعة')->assertSee('سائق');
        // بلا صلاحية الإجازات لا تظهر في الملف
        $noLeaves = $this->user('n@example.com', 'user', ['employees.view']);
        $this->actingAs($noLeaves)->get("/employees/{$e->id}/print")->assertOk()->assertSee('IQ-PRINT')->assertDontSee('ظرف عائلي');
    }

    public function test_register_and_csv_export(): void
    {
        $v = $this->user('v@example.com');
        $c1 = $this->company('الأولى');
        $c2 = $this->company('الثانية');
        $e1 = $this->emp($c1, 'خالد', ['code' => 'E1', 'nationality' => 'سعودي', 'job_title' => 'محاسب']);
        $evil = $this->emp($c2, '=cmd|calc', ['code' => 'E2']);
        $this->emp($c1, 'متقاعد', ['status' => 'inactive']);
        $e1->documents()->create(['type' => 'iqama', 'number' => '2111111111', 'expiry_date' => '2027-05-05']);
        $e1->documents()->create(['type' => 'insurance', 'number' => 'POL-1', 'provider' => 'بوبا', 'expiry_date' => '2026-12-31']);

        $this->actingAs($v)->get('/employees/register')->assertOk()->assertSee('خالد')->assertSee('2111111111')->assertSee('بوبا')->assertDontSee('متقاعد');
        $this->actingAs($v)->get('/employees/register?status=all')->assertSee('متقاعد');

        $res = $this->actingAs($v)->get('/employees/export');
        $res->assertOk();
        $this->assertStringStartsWith('text/csv', $res->headers->get('Content-Type'));
        $body = $res->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'BOM لفتح العربية في إكسل');
        $this->assertStringContainsString('2111111111', $body);
        $this->assertStringContainsString('POL-1', $body);
        $this->assertStringNotContainsString('متقاعد', $body);
        // حماية من حقن المعادلات في إكسل
        $this->assertStringContainsString("'=cmd|calc", $body);
        $this->assertStringNotContainsString(',=cmd', $body);
        $this->assertStringNotContainsString("\n=cmd", $body);
        // تقييد بالشركة المختارة
        $this->actingAs($v)->post('/company/switch', ['company' => $c1->id]);
        $scoped = $this->actingAs($v->fresh())->get('/employees/export')->streamedContent();
        $this->assertStringContainsString('خالد', $scoped);
        $this->assertStringNotContainsString('cmd', $scoped);
        $this->assertNotNull($evil);
    }
}

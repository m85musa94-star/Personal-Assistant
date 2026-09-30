<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role = 'user'): User
    {
        $u = new User(['name' => $email, 'email' => $email, 'password' => 'correct-horse-1']);
        $u->is_admin = $role === 'admin';
        $u->is_hr = $role === 'hr';
        $u->save();

        return $u;
    }

    private function emp(string $name, ?User $user = null, ?Employee $manager = null, array $extra = []): Employee
    {
        return Employee::create(['name' => $name, 'user_id' => $user?->id, 'manager_id' => $manager?->id] + $extra);
    }

    private function annual(): LeaveType
    {
        return LeaveType::where('name_en', 'Annual leave')->firstOrFail();
    }

    // ---------- الوصول والصلاحيات ----------

    public function test_guest_is_redirected(): void
    {
        foreach (['/hr', '/hr/employees', '/hr/leave', '/hr/attendance', '/hr/org'] as $u) {
            $this->get($u)->assertRedirect('/login');
        }
    }

    public function test_plain_user_can_browse_but_not_manage(): void
    {
        $u = $this->user('u@example.com');
        $this->actingAs($u)->get('/hr')->assertOk()->assertSee('غير مرتبط بموظف');
        $this->actingAs($u)->get('/hr/employees')->assertOk();
        $this->actingAs($u)->get('/hr/employees/create')->assertForbidden();
        $this->actingAs($u)->post('/hr/employees', ['name' => 'x', 'status' => 'active'])->assertForbidden();
        $this->actingAs($u)->get('/hr/departments')->assertForbidden();
        $this->actingAs($u)->get('/hr/leave-types')->assertForbidden();
        $this->actingAs($u)->post('/hr/departments', ['name' => 'x'])->assertForbidden();
    }

    public function test_sensitive_fields_visible_only_to_hr_or_self(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $other = $this->user('o@example.com');
        $target = $this->emp('هدف', null, null, ['salary' => 12345.67, 'id_number' => '1029384756']);
        $this->actingAs($hr)->get("/hr/employees/{$target->id}")->assertSee('12,345.67')->assertSee('1029384756');
        $this->actingAs($other)->get("/hr/employees/{$target->id}")->assertOk()->assertDontSee('12,345.67')->assertDontSee('1029384756');
        $mine = $this->emp('أنا', $other, null, ['id_number' => '5555555555', 'salary' => 999]);
        $this->actingAs($other)->get("/hr/employees/{$mine->id}")->assertSee('5555555555')->assertDontSee('999.00');
    }

    // ---------- الموظفون والأقسام ----------

    public function test_hr_creates_and_edits_employee(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $dept = Department::create(['name' => 'المالية', 'name_en' => 'Finance']);
        $this->actingAs($hr)->post('/hr/employees', ['name' => 'سارة', 'name_en' => 'Sara', 'status' => 'active', 'department_id' => $dept->id, 'code' => 'E-1', 'salary' => '5000', 'hire_date' => '2025-01-15'])
            ->assertRedirect();
        $e = Employee::first();
        $this->assertSame('E-1', $e->code);
        $this->actingAs($hr)->put("/hr/employees/{$e->id}", ['name' => 'سارة أحمد', 'name_en' => '', 'status' => 'inactive', 'code' => 'E-1'])->assertRedirect();
        $this->assertSame('inactive', $e->fresh()->status);
        $this->assertNull($e->fresh()->name_en);
    }

    public function test_employee_code_and_user_link_are_unique(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $u = $this->user('u@example.com');
        $this->emp('أ', $u, null, ['code' => 'X1']);
        $this->actingAs($hr)->post('/hr/employees', ['name' => 'ب', 'status' => 'active', 'code' => 'X1'])->assertSessionHasErrors('code');
        $this->actingAs($hr)->post('/hr/employees', ['name' => 'ج', 'status' => 'active', 'user_id' => $u->id])->assertSessionHasErrors('user_id');
    }

    public function test_manager_cycle_is_rejected(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $a = $this->emp('A');
        $b = $this->emp('B', null, $a);
        $c = $this->emp('C', null, $b);
        $this->actingAs($hr)->put("/hr/employees/{$a->id}", ['name' => 'A', 'status' => 'active', 'manager_id' => $c->id])->assertSessionHasErrors('manager_id');
        $this->assertNull($a->fresh()->manager_id);
    }

    public function test_contract_end_before_start_rejected(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $this->actingAs($hr)->post('/hr/employees', ['name' => 'x', 'status' => 'active', 'contract_start' => '2026-05-01', 'contract_end' => '2026-04-01'])->assertSessionHasErrors('contract_end');
    }

    public function test_employee_search_and_filters(): void
    {
        $u = $this->user('u@example.com');
        $d = Department::create(['name' => 'المبيعات']);
        $this->emp('خالد المبيعات', null, null, ['department_id' => $d->id, 'job_title' => 'مندوب']);
        $this->emp('منى', null, null, ['status' => 'inactive']);
        $this->actingAs($u)->get('/hr/employees?q=خالد')->assertSee('خالد المبيعات')->assertDontSee('منى');
        $this->actingAs($u)->get('/hr/employees')->assertDontSee('منى'); // النشطون افتراضيًا
        $this->actingAs($u)->get('/hr/employees?status=inactive')->assertSee('منى');
        $this->actingAs($u)->get("/hr/employees?department={$d->id}&view=list")->assertSee('خالد المبيعات');
    }

    public function test_department_crud_and_delete_guard(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $this->actingAs($hr)->post('/hr/departments', ['name' => 'التشغيل', 'name_en' => 'Operations'])->assertSessionHas('ok');
        $d = Department::first();
        $this->emp('موظف', null, null, ['department_id' => $d->id]);
        $this->actingAs($hr)->delete("/hr/departments/{$d->id}")->assertSessionHas('warn');
        $this->assertNotNull($d->fresh());
        Employee::query()->update(['department_id' => null]);
        $this->actingAs($hr)->delete("/hr/departments/{$d->id}")->assertSessionHas('ok');
        $this->assertNull($d->fresh());
    }

    public function test_only_admin_deletes_employee(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $admin = $this->user('admin@example.com', 'admin');
        $e = $this->emp('x');
        $this->actingAs($hr)->delete("/hr/employees/{$e->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/hr/employees/{$e->id}")->assertRedirect();
        $this->assertNull($e->fresh());
    }

    public function test_org_chart_renders_hierarchy(): void
    {
        $u = $this->user('u@example.com');
        $boss = $this->emp('الرئيس', null, null, ['job_title' => 'مدير عام']);
        $this->emp('المرؤوس', null, $boss);
        $this->emp('خارج الخدمة', null, null, ['status' => 'inactive']);
        $this->actingAs($u)->get('/hr/org')->assertOk()->assertSee('الرئيس')->assertSee('المرؤوس')->assertDontSee('خارج الخدمة');
    }

    // ---------- الإجازات ----------

    public function test_leave_request_flow_with_manager_approval(): void
    {
        $mgrUser = $this->user('m@example.com');
        $staffUser = $this->user('s@example.com');
        $mgr = $this->emp('المدير', $mgrUser);
        $staff = $this->emp('الموظف', $staffUser, $mgr);
        $type = $this->annual();

        $this->actingAs($staffUser)->post('/hr/leave', ['leave_type_id' => $type->id, 'start_date' => '2030-03-01', 'end_date' => '2030-03-05'])->assertRedirect();
        $leave = LeaveRequest::first();
        $this->assertSame('pending', $leave->status);
        $this->assertEquals(5.0, $leave->days);

        // الموظف لا يعتمد طلب نفسه
        $this->actingAs($staffUser)->post("/hr/leave/{$leave->id}/approve")->assertForbidden();
        // المدير المباشر يعتمد
        $this->actingAs($mgrUser)->post("/hr/leave/{$leave->id}/approve")->assertSessionHas('ok');
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame($mgrUser->id, $leave->fresh()->decided_by);
        // لا قرار ثانٍ
        $this->actingAs($mgrUser)->post("/hr/leave/{$leave->id}/reject", ['decision_note' => 'x'])->assertSessionHas('warn');
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_unrelated_user_cannot_decide(): void
    {
        $a = $this->user('a@example.com');
        $b = $this->user('b@example.com');
        $this->emp('A', $a);
        $eb = $this->emp('B', $b);
        $leave = LeaveRequest::create(['employee_id' => $eb->id, 'leave_type_id' => $this->annual()->id, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2]);
        $this->actingAs($a)->post("/hr/leave/{$leave->id}/approve")->assertForbidden();
    }

    public function test_reject_requires_reason(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $s = $this->user('s@example.com');
        $e = $this->emp('S', $s);
        $leave = LeaveRequest::create(['employee_id' => $e->id, 'leave_type_id' => $this->annual()->id, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2]);
        $this->actingAs($hr)->post("/hr/leave/{$leave->id}/reject", [])->assertSessionHasErrors('decision_note');
        $this->actingAs($hr)->post("/hr/leave/{$leave->id}/reject", ['decision_note' => 'ضغط العمل'])->assertSessionHas('ok');
        $this->assertSame('rejected', $leave->fresh()->status);
        $this->assertSame('ضغط العمل', $leave->fresh()->decision_note);
    }

    public function test_hr_cannot_approve_own_request_but_admin_can(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $admin = $this->user('admin@example.com', 'admin');
        $eh = $this->emp('HR', $hr);
        $ea = $this->emp('Admin', $admin);
        $t = $this->annual()->id;
        $l1 = LeaveRequest::create(['employee_id' => $eh->id, 'leave_type_id' => $t, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2]);
        $l2 = LeaveRequest::create(['employee_id' => $ea->id, 'leave_type_id' => $t, 'start_date' => '2030-02-01', 'end_date' => '2030-02-02', 'days' => 2]);
        $this->actingAs($hr)->post("/hr/leave/{$l1->id}/approve")->assertForbidden();
        $this->actingAs($admin)->post("/hr/leave/{$l1->id}/approve")->assertSessionHas('ok'); // مدير النظام يعتمد لغيره
        $this->actingAs($admin)->post("/hr/leave/{$l2->id}/approve")->assertSessionHas('ok'); // ولنفسه (لا أحد فوقه)
    }

    public function test_leave_validation_overlap_days_and_balance(): void
    {
        $u = $this->user('s@example.com');
        $this->emp('S', $u);
        $type = $this->annual();
        $type->update(['annual_days' => 5]);

        // أيام أكثر من المدة
        $this->actingAs($u)->post('/hr/leave', ['leave_type_id' => $type->id, 'start_date' => '2030-03-01', 'end_date' => '2030-03-02', 'days' => 3])->assertSessionHasErrors('days');
        // نهاية قبل البداية
        $this->actingAs($u)->post('/hr/leave', ['leave_type_id' => $type->id, 'start_date' => '2030-03-05', 'end_date' => '2030-03-01'])->assertSessionHasErrors('end_date');
        // ضمن الرصيد
        $this->actingAs($u)->post('/hr/leave', ['leave_type_id' => $type->id, 'start_date' => '2030-03-01', 'end_date' => '2030-03-03'])->assertSessionHasNoErrors();
        // تقاطع
        $this->actingAs($u)->post('/hr/leave', ['leave_type_id' => $type->id, 'start_date' => '2030-03-03', 'end_date' => '2030-03-04'])->assertSessionHasErrors('start_date');
        // يتجاوز الرصيد (3 مستخدمة + 3 > 5)
        $this->actingAs($u)->post('/hr/leave', ['leave_type_id' => $type->id, 'start_date' => '2030-04-01', 'end_date' => '2030-04-03'])->assertSessionHasErrors('days');
        // نوع بلا استحقاق محدد: لا فحص رصيد
        $other = LeaveType::where('name_en', 'Other leave')->first();
        $this->actingAs($u)->post('/hr/leave', ['leave_type_id' => $other->id, 'start_date' => '2030-05-01', 'end_date' => '2030-05-20'])->assertSessionHasNoErrors();
        $this->assertSame(2, LeaveRequest::count());
    }

    public function test_hr_records_leave_for_others_with_immediate_approval(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $e = $this->emp('موظف');
        $this->actingAs($hr)->post('/hr/leave', ['employee_id' => $e->id, 'leave_type_id' => $this->annual()->id, 'start_date' => '2030-06-01', 'end_date' => '2030-06-02', 'approve_now' => '1'])->assertSessionHas('ok');
        $this->assertSame('approved', LeaveRequest::first()->status);
    }

    public function test_user_without_employee_cannot_request_leave(): void
    {
        $u = $this->user('u@example.com');
        $this->actingAs($u)->get('/hr/leave/create')->assertForbidden();
        $this->actingAs($u)->post('/hr/leave', ['leave_type_id' => $this->annual()->id, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02'])->assertForbidden();
    }

    public function test_cancel_rules(): void
    {
        $s = $this->user('s@example.com');
        $other = $this->user('o@example.com');
        $e = $this->emp('S', $s);
        $this->emp('O', $other);
        $t = $this->annual()->id;
        $future = LeaveRequest::create(['employee_id' => $e->id, 'leave_type_id' => $t, 'start_date' => today()->addDays(10), 'end_date' => today()->addDays(11), 'days' => 2, 'status' => 'approved']);
        $past = LeaveRequest::create(['employee_id' => $e->id, 'leave_type_id' => $t, 'start_date' => today()->subDays(10), 'end_date' => today()->subDays(9), 'days' => 2, 'status' => 'approved']);
        $this->actingAs($other)->post("/hr/leave/{$future->id}/cancel")->assertForbidden();
        $this->actingAs($s)->post("/hr/leave/{$past->id}/cancel")->assertSessionHas('warn');
        $this->assertSame('approved', $past->fresh()->status);
        $this->actingAs($s)->post("/hr/leave/{$future->id}/cancel")->assertSessionHas('ok');
        $this->assertSame('cancelled', $future->fresh()->status);
    }

    public function test_leave_list_scopes(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $s = $this->user('s@example.com');
        $t = $this->emp('الموظف أ', $s);
        $x = $this->emp('موظف ب');
        $ty = $this->annual()->id;
        LeaveRequest::create(['employee_id' => $t->id, 'leave_type_id' => $ty, 'start_date' => '2030-01-01', 'end_date' => '2030-01-01', 'days' => 1, 'reason' => 'سبب أ']);
        LeaveRequest::create(['employee_id' => $x->id, 'leave_type_id' => $ty, 'start_date' => '2030-01-02', 'end_date' => '2030-01-02', 'days' => 1, 'reason' => 'سبب ب']);
        $this->actingAs($s)->get('/hr/leave')->assertSee('سبب أ')->assertDontSee('سبب ب');
        $this->actingAs($hr)->get('/hr/leave?tab=all')->assertSee('سبب أ')->assertSee('سبب ب');
        $this->actingAs($hr)->get('/hr/leave?tab=all&layout=board')->assertOk()->assertSee('col pending', false);
    }

    public function test_leave_type_admin_and_delete_guard(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $this->actingAs($hr)->post('/hr/leave-types', ['name' => 'إجازة زواج', 'name_en' => 'Marriage leave', 'annual_days' => '', 'is_paid' => '1', 'is_active' => '1'])->assertSessionHas('ok');
        $t = LeaveType::where('name_en', 'Marriage leave')->first();
        $this->assertNull($t->annual_days, 'الاستحقاق الفارغ يبقى NULL وليس صفرًا');
        $this->actingAs($hr)->put("/hr/leave-types/{$t->id}", ['name' => 'إجازة زواج', 'annual_days' => '3', 'is_paid' => '1', 'is_active' => '1'])->assertSessionHas('ok');
        $this->assertEquals(3.0, $t->fresh()->annual_days);
        $e = $this->emp('x');
        LeaveRequest::create(['employee_id' => $e->id, 'leave_type_id' => $t->id, 'start_date' => '2030-01-01', 'end_date' => '2030-01-01', 'days' => 1]);
        $this->actingAs($hr)->delete("/hr/leave-types/{$t->id}")->assertSessionHas('warn');
        $this->assertNotNull($t->fresh());
    }

    public function test_seeded_leave_types_have_no_assumed_entitlements(): void
    {
        $this->assertSame(4, LeaveType::count());
        $this->assertSame(0, LeaveType::whereNotNull('annual_days')->count());
    }

    // ---------- الحضور ----------

    public function test_clock_in_out_flow(): void
    {
        $u = $this->user('s@example.com');
        $e = $this->emp('S', $u);
        $this->actingAs($u)->post('/hr/attendance/clock-out')->assertSessionHas('warn');
        $this->actingAs($u)->post('/hr/attendance/clock-in')->assertSessionHas('ok');
        $this->actingAs($u)->post('/hr/attendance/clock-in')->assertSessionHas('warn'); // مفتوح مسبقًا
        $this->assertSame(1, AttendanceRecord::count());
        $this->actingAs($u)->post('/hr/attendance/clock-out')->assertSessionHas('ok');
        $this->assertNotNull(AttendanceRecord::first()->check_out);
        $this->actingAs($u)->get('/hr/attendance')->assertOk();
    }

    public function test_clock_requires_linked_employee(): void
    {
        $u = $this->user('u@example.com');
        $this->actingAs($u)->post('/hr/attendance/clock-in')->assertForbidden();
    }

    public function test_attendance_visibility_and_manual_records(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $a = $this->user('a@example.com');
        $b = $this->user('b@example.com');
        $ea = $this->emp('أحمد', $a);
        $eb = $this->emp('بدر', $b);
        AttendanceRecord::create(['employee_id' => $ea->id, 'check_in' => now()->subHours(9), 'check_out' => now()->subHour(), 'note' => 'ملاحظة-أحمد']);
        AttendanceRecord::create(['employee_id' => $eb->id, 'check_in' => now()->subHours(9), 'check_out' => now()->subHour(), 'note' => 'ملاحظة-بدر']);
        $this->actingAs($a)->get('/hr/attendance')->assertSee('ملاحظة-أحمد')->assertDontSee('ملاحظة-بدر');
        $this->actingAs($hr)->get('/hr/attendance')->assertSee('ملاحظة-أحمد')->assertSee('ملاحظة-بدر');
        // يدوي: HR فقط
        $payload = ['employee_id' => $ea->id, 'check_in' => now()->subDays(1)->format('Y-m-d\TH:i'), 'check_out' => now()->subDays(1)->addHours(8)->format('Y-m-d\TH:i')];
        $this->actingAs($a)->post('/hr/attendance', $payload)->assertForbidden();
        $this->actingAs($hr)->post('/hr/attendance', $payload)->assertSessionHas('ok');
        $this->assertSame('manual', AttendanceRecord::latest('id')->first()->source);
        $bad = ['employee_id' => $ea->id, 'check_in' => '2030-01-01T10:00', 'check_out' => '2030-01-01T09:00'];
        $this->actingAs($hr)->post('/hr/attendance', $bad)->assertSessionHasErrors('check_out');
    }

    public function test_line_manager_sees_team_attendance(): void
    {
        $m = $this->user('m@example.com');
        $s = $this->user('s@example.com');
        $x = $this->user('x@example.com');
        $em = $this->emp('المدير', $m);
        $es = $this->emp('التابع', $s, $em);
        $ex = $this->emp('الغريب', $x);
        foreach ([$es, $ex] as $e) {
            AttendanceRecord::create(['employee_id' => $e->id, 'check_in' => now()->subHours(3), 'check_out' => now()->subHour(), 'note' => 'n'.$e->id]);
        }
        $this->actingAs($m)->get('/hr/attendance')->assertSee('n'.$es->id)->assertDontSee('n'.$ex->id);
    }

    public function test_dashboard_lists_pending_and_expiring(): void
    {
        $hr = $this->user('hr@example.com', 'hr');
        $e = $this->emp('منتهي الإقامة', null, null, ['id_expiry' => today()->subDays(3)]);
        LeaveRequest::create(['employee_id' => $e->id, 'leave_type_id' => $this->annual()->id, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2]);
        $this->actingAs($hr)->get('/hr')->assertOk()->assertSee('منتهي الإقامة');
        $plain = $this->user('p@example.com');
        $this->actingAs($plain)->get('/hr')->assertOk()->assertDontSee('وثائق وعقود منتهية');
    }
}

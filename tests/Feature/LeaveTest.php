<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesData;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use MakesData, RefreshDatabase;

    public function test_seeded_types_have_no_assumed_entitlements(): void
    {
        $this->assertSame(4, LeaveType::count());
        $this->assertSame(0, LeaveType::whereNotNull('annual_days')->count());
    }

    public function test_record_leave_and_validation(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $e = $this->emp($this->company());
        $t = $this->annual()->id;
        $this->actingAs($m)->get('/leaves/create')->assertOk();
        $this->actingAs($m)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => $t, 'start_date' => '2030-03-01', 'end_date' => '2030-03-05'])->assertRedirect('/leaves');
        $l = LeaveRequest::first();
        $this->assertSame('approved', $l->status);
        $this->assertEquals(5.0, $l->days);
        // أيام أكثر من المدة
        $this->actingAs($m)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => $t, 'start_date' => '2030-04-01', 'end_date' => '2030-04-02', 'days' => 3])->assertSessionHasErrors('days');
        // نهاية قبل البداية
        $this->actingAs($m)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => $t, 'start_date' => '2030-04-05', 'end_date' => '2030-04-01'])->assertSessionHasErrors('end_date');
        // تقاطع
        $this->actingAs($m)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => $t, 'start_date' => '2030-03-04', 'end_date' => '2030-03-08'])->assertSessionHasErrors('start_date');
        // نصف يوم
        $this->actingAs($m)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => $t, 'start_date' => '2030-05-01', 'end_date' => '2030-05-01', 'days' => 0.5])->assertSessionHasNoErrors();
        $this->assertSame(2, LeaveRequest::count());
    }

    public function test_viewer_cannot_record(): void
    {
        $v = $this->user();
        $e = $this->emp($this->company());
        $this->actingAs($v)->get('/leaves/create')->assertForbidden();
        $this->actingAs($v)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => $this->annual()->id, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02'])->assertForbidden();
        $this->actingAs($v)->get('/leaves')->assertOk();
    }

    public function test_over_balance_warns_but_records(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $e = $this->emp($this->company());
        $type = $this->annual();
        $type->update(['annual_days' => 3]);
        $this->actingAs($m)->post('/leaves', ['employee_id' => $e->id, 'leave_type_id' => $type->id, 'start_date' => '2030-03-01', 'end_date' => '2030-03-05'])
            ->assertSessionHas('ok')->assertSessionHas('warn');
        $this->assertSame(1, LeaveRequest::count());
        $this->assertEquals(-2.0, $e->leaveBalance($type, 2030));
        // النوع بلا استحقاق: لا رصيد
        $other = LeaveType::where('name_en', 'Other leave')->first();
        $this->assertNull($e->leaveBalance($other));
    }

    public function test_cancel_and_restore_with_overlap_guard(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $e = $this->emp($this->company());
        $t = $this->annual()->id;
        $a = $e->leaves()->create(['leave_type_id' => $t, 'start_date' => '2030-06-01', 'end_date' => '2030-06-05', 'days' => 5, 'status' => 'approved']);
        $this->actingAs($m)->post("/leaves/{$a->id}/toggle")->assertSessionHas('ok');
        $this->assertSame('cancelled', $a->fresh()->status);
        // سجل جديد في نفس الفترة بعد الإلغاء مقبول
        $b = $e->leaves()->create(['leave_type_id' => $t, 'start_date' => '2030-06-03', 'end_date' => '2030-06-04', 'days' => 2, 'status' => 'approved']);
        // استعادة الملغاة تتعارض
        $this->actingAs($m)->post("/leaves/{$a->id}/toggle")->assertSessionHas('warn');
        $this->assertSame('cancelled', $a->fresh()->status);
        $this->assertNotNull($b->fresh());
    }

    public function test_list_filters_and_company_scope(): void
    {
        $v = $this->user();
        $c1 = $this->company('الأولى');
        $c2 = $this->company('الثانية');
        $e1 = $this->emp($c1, 'أحمد-أ');
        $e2 = $this->emp($c2, 'بدر-ب');
        $t = $this->annual()->id;
        $e1->leaves()->create(['leave_type_id' => $t, 'start_date' => '2030-01-01', 'end_date' => '2030-01-02', 'days' => 2, 'status' => 'approved', 'reason' => 'سبب-أ']);
        $e2->leaves()->create(['leave_type_id' => $t, 'start_date' => '2031-01-01', 'end_date' => '2031-01-02', 'days' => 2, 'status' => 'approved', 'reason' => 'سبب-ب']);
        $this->actingAs($v)->get('/leaves')->assertSee('سبب-أ')->assertSee('سبب-ب');
        $this->actingAs($v)->get('/leaves?year=2030')->assertSee('سبب-أ')->assertDontSee('سبب-ب');
        $this->actingAs($v)->get("/leaves?employee={$e2->id}")->assertSee('سبب-ب')->assertDontSee('سبب-أ');
        $this->actingAs($v)->post('/company/switch', ['company' => $c1->id]);
        $this->actingAs($v->fresh())->get('/leaves')->assertSee('سبب-أ')->assertDontSee('سبب-ب');
    }
}

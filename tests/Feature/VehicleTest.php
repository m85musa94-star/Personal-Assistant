<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesData;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use MakesData, RefreshDatabase;

    public function test_create_update_and_status_bar(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $c = $this->company();
        $driver = $this->emp($c, 'السائق');
        $this->actingAs($m)->get('/vehicles/create')->assertOk();
        $this->actingAs($m)->post('/vehicles', ['company_id' => $c->id, 'plate' => 'أ ب ج 1234', 'make' => 'تويوتا', 'model' => 'هايلكس', 'year' => 2022, 'type' => 'pickup', 'fuel' => 'petrol', 'status' => 'active', 'driver_id' => $driver->id, 'odometer' => 45000])->assertRedirect();
        $v = Vehicle::first();
        $this->assertSame('pickup', $v->type);
        $this->assertSame($driver->id, $v->driver->id);
        $this->actingAs($m)->put("/vehicles/{$v->id}/status", ['status' => 'maintenance'])->assertSessionHas('ok');
        $this->assertSame('maintenance', $v->fresh()->status);
        $this->actingAs($m)->put("/vehicles/{$v->id}/status", ['status' => 'flying'])->assertSessionHasErrors('status');
        $this->actingAs($m)->put("/vehicles/{$v->id}", ['company_id' => $c->id, 'plate' => 'أ ب ج 1234', 'status' => 'active', 'year' => 1800])->assertSessionHasErrors('year');
        $this->actingAs($m)->put("/vehicles/{$v->id}", ['company_id' => $c->id, 'plate' => 'أ ب ج 1234', 'status' => 'active', 'make' => 'تويوتا', 'model' => ''])->assertRedirect();
        $this->assertNull($v->fresh()->model);
        $this->actingAs($m)->get("/vehicles/{$v->id}")->assertOk()->assertSee('تويوتا');
    }

    public function test_viewer_read_only_and_admin_delete(): void
    {
        $viewer = $this->user();
        $hr = $this->user('hr@example.com', 'hr');
        $admin = $this->user('a@example.com', 'admin');
        $c = $this->company();
        $v = $this->car($c);
        $this->actingAs($viewer)->get("/vehicles/{$v->id}")->assertOk();
        $this->actingAs($viewer)->put("/vehicles/{$v->id}/status", ['status' => 'sold'])->assertForbidden();
        $this->actingAs($viewer)->post("/vehicles/{$v->id}/records", ['type' => 'fuel', 'record_date' => '2026-01-01'])->assertForbidden();
        $this->actingAs($viewer)->post("/vehicles/{$v->id}/documents", ['type' => 'registration'])->assertForbidden();
        $this->actingAs($hr)->delete("/vehicles/{$v->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/vehicles/{$v->id}")->assertRedirect('/vehicles');
        $this->assertNull($v->fresh());
    }

    public function test_documents_and_renewal(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $v = $this->car($this->company());
        foreach (['registration', 'insurance', 'inspection', 'operating_card'] as $type) {
            $this->actingAs($m)->post("/vehicles/{$v->id}/documents", ['type' => $type, 'number' => strtoupper($type), 'expiry_date' => today()->addDays(10)->toDateString()])->assertRedirect();
        }
        $this->assertSame(4, $v->documents()->count());
        $doc = $v->documents()->where('type', 'insurance')->first();
        $this->assertSame('amb', $doc->tone());
        $this->actingAs($m)->put("/vehicle-documents/{$doc->id}", ['type' => 'insurance', 'expiry_date' => today()->addYear()->toDateString()])->assertRedirect();
        $this->assertSame('', $doc->fresh()->tone());
        $this->actingAs($m)->post("/vehicles/{$v->id}/documents", ['type' => 'employee_only'])->assertSessionHasErrors('type');
        $this->actingAs($m)->post("/vehicles/{$v->id}/documents", ['type' => 'iqama'])->assertSessionHasErrors('type');
        $this->actingAs($m)->delete("/vehicle-documents/{$doc->id}")->assertRedirect();
        $this->assertSame(3, $v->documents()->count());
    }

    public function test_records_totals_and_odometer_sync(): void
    {
        $m = $this->user('m@example.com', 'hr');
        $v = $this->car($this->company(), 'XYZ 999', ['odometer' => 10000]);
        $this->actingAs($m)->post("/vehicles/{$v->id}/records", ['type' => 'service', 'record_date' => '2026-02-01', 'odometer' => 12000, 'amount' => '450.50', 'vendor' => 'ورشة الأمل', 'description' => 'تغيير زيت'])->assertRedirect();
        $this->actingAs($m)->post("/vehicles/{$v->id}/records", ['type' => 'fine', 'record_date' => '2026-03-01', 'odometer' => 11000, 'amount' => '300', 'description' => 'تجاوز سرعة'])->assertRedirect();
        $this->assertSame(12000, $v->fresh()->odometer, 'العداد لا ينخفض بقراءة أقدم');
        $this->assertEquals(750.50, $v->records()->sum('amount'));
        $this->actingAs($m)->get("/vehicles/{$v->id}?tab=records")->assertOk()->assertSee('750.50')->assertSee('ورشة الأمل')->assertSee('تجاوز سرعة');
        $this->actingAs($m)->post("/vehicles/{$v->id}/records", ['type' => 'nonsense', 'record_date' => '2026-01-01'])->assertSessionHasErrors('type');
        $this->actingAs($m)->post("/vehicles/{$v->id}/records", ['type' => 'fuel'])->assertSessionHasErrors('record_date');
        $rec = $v->records()->first();
        $this->actingAs($m)->delete("/vehicle-records/{$rec->id}")->assertRedirect();
        $this->assertSame(1, $v->records()->count());
    }

    public function test_records_index_filters_and_total(): void
    {
        $u = $this->user();
        $c1 = $this->company('ا');
        $c2 = $this->company('ب');
        $v1 = $this->car($c1, 'AAA 111');
        $v2 = $this->car($c2, 'BBB 222');
        $v1->records()->create(['type' => 'fuel', 'record_date' => '2030-01-01', 'amount' => 100, 'description' => 'وصف-1']);
        $v2->records()->create(['type' => 'fine', 'record_date' => '2031-01-01', 'amount' => 50, 'description' => 'وصف-2']);
        $this->actingAs($u)->get('/vehicle-records')->assertSee('وصف-1')->assertSee('وصف-2')->assertSee('150.00');
        $this->actingAs($u)->get('/vehicle-records?type=fine')->assertSee('وصف-2')->assertDontSee('وصف-1')->assertSee('50.00');
        $this->actingAs($u)->get("/vehicle-records?vehicle={$v1->id}")->assertSee('وصف-1')->assertDontSee('وصف-2');
        $this->actingAs($u)->get('/vehicle-records?year=2031')->assertSee('وصف-2')->assertDontSee('وصف-1');
        $this->actingAs($u)->post('/company/switch', ['company' => $c1->id]);
        $this->actingAs($u->fresh())->get('/vehicle-records')->assertSee('وصف-1')->assertDontSee('وصف-2');
    }

    public function test_list_filters(): void
    {
        $u = $this->user();
        $c = $this->company();
        $this->car($c, 'LIVE 1', ['make' => 'نيسان']);
        $this->car($c, 'SOLD 2', ['status' => 'sold']);
        $this->car($c, 'MAINT 3', ['status' => 'maintenance']);
        $this->actingAs($u)->get('/vehicles')->assertSee('LIVE 1')->assertSee('MAINT 3')->assertDontSee('SOLD 2');
        $this->actingAs($u)->get('/vehicles?status=sold')->assertSee('SOLD 2')->assertDontSee('LIVE 1');
        $this->actingAs($u)->get('/vehicles?status=all&view=list')->assertSee('SOLD 2')->assertSee('LIVE 1');
        $this->actingAs($u)->get('/vehicles?q=نيسان')->assertSee('LIVE 1')->assertDontSee('MAINT 3');
    }
}

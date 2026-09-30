<?php

namespace Tests\Feature;

use App\Support\Alerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesData;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use MakesData, RefreshDatabase;

    private function seedDocs(): array
    {
        $c1 = $this->company('الأولى');
        $c2 = $this->company('الثانية');
        $e1 = $this->emp($c1, 'أحمد-منتهي');
        $e2 = $this->emp($c2, 'بدر-قريب');
        $gone = $this->emp($c1, 'خرج', ['status' => 'inactive']);
        $fine = $this->emp($c1, 'سليم');
        $e1->documents()->create(['type' => 'iqama', 'number' => 'IQ-EXPIRED', 'expiry_date' => today()->subDays(5)]);
        $e2->documents()->create(['type' => 'insurance', 'number' => 'INS-SOON', 'expiry_date' => today()->addDays(25)]);
        $gone->documents()->create(['type' => 'iqama', 'number' => 'IQ-INACTIVE', 'expiry_date' => today()->subDays(1)]);
        $fine->documents()->create(['type' => 'iqama', 'number' => 'IQ-FINE', 'expiry_date' => today()->addYear()]);
        $v1 = $this->car($c1, 'CAR-EXP');
        $sold = $this->car($c1, 'CAR-SOLD', ['status' => 'sold']);
        $v1->documents()->create(['type' => 'registration', 'number' => 'REG-EXP', 'expiry_date' => today()->subDays(20)]);
        $v1->documents()->create(['type' => 'inspection', 'number' => 'INSP-90', 'expiry_date' => today()->addDays(80)]);
        $sold->documents()->create(['type' => 'registration', 'number' => 'REG-SOLD', 'expiry_date' => today()->subDays(3)]);

        return [$c1, $c2];
    }

    public function test_alerts_list_and_exclusions(): void
    {
        $u = $this->user();
        $this->seedDocs();
        $r = $this->actingAs($u)->get('/alerts');
        $r->assertOk()->assertSee('IQ-EXPIRED')->assertSee('INS-SOON')->assertSee('REG-EXP');
        $r->assertDontSee('IQ-INACTIVE')->assertDontSee('REG-SOLD')->assertDontSee('IQ-FINE')->assertDontSee('INSP-90');
        // 90 يومًا تضيف الفحص
        $this->actingAs($u)->get('/alerts?days=90')->assertSee('INSP-90');
        // المنتهية فقط
        $this->actingAs($u)->get('/alerts?days=0')->assertSee('IQ-EXPIRED')->assertSee('REG-EXP')->assertDontSee('INS-SOON');
    }

    public function test_scope_and_company_filters(): void
    {
        $u = $this->user();
        [$c1, $c2] = $this->seedDocs();
        $this->actingAs($u)->get('/alerts?scope=employees')->assertSee('IQ-EXPIRED')->assertDontSee('REG-EXP');
        $this->actingAs($u)->get('/alerts?scope=vehicles')->assertSee('REG-EXP')->assertDontSee('IQ-EXPIRED');
        $this->actingAs($u)->post('/company/switch', ['company' => $c2->id]);
        $this->actingAs($u->fresh())->get('/alerts')->assertSee('INS-SOON')->assertDontSee('IQ-EXPIRED')->assertDontSee('REG-EXP');
    }

    public function test_counts_and_badge(): void
    {
        $u = $this->user();
        $this->seedDocs();
        $this->actingAs($u);
        $this->get('/'); // يهيّئ الطلب
        $counts = Alerts::counts();
        $this->assertSame(['expired' => 2, 'soon' => 1, 'total' => 3], $counts);
        $this->get('/')->assertSee('o-badge red', false);
    }

    public function test_home_lists_urgent_documents(): void
    {
        $u = $this->user();
        $this->seedDocs();
        $this->actingAs($u)->get('/')->assertSee('IQ-EXPIRED')->assertSee('أقرب الوثائق انتهاءً');
    }

    public function test_dashboard_alert_filter_on_vehicles(): void
    {
        $u = $this->user();
        $this->seedDocs();
        $this->actingAs($u)->get('/vehicles?alerts=1')->assertSee('CAR-EXP');
    }
}

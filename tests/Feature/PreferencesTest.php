<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = new User(['name' => 'أحمد', 'email' => 'a@example.com', 'password' => 'correct-horse-1']);
        $u->is_admin = true;
        $u->save();

        return $u;
    }

    public function test_default_is_arabic_rtl(): void
    {
        $this->get('/login')->assertOk()->assertSee('lang="ar" dir="rtl"', false)->assertSee('تسجيل الدخول');
    }

    public function test_guest_switches_language_via_cookie(): void
    {
        $this->post('/preferences', ['locale' => 'en'])->assertRedirect();
        $this->withCookie('locale', 'en')->get('/login')->assertSee('lang="en" dir="ltr"', false)->assertSee('Sign in');
    }

    public function test_user_preferences_persist_and_apply(): void
    {
        $u = $this->admin();
        $this->actingAs($u)->postJson('/preferences', ['locale' => 'en', 'theme' => 'dark'])->assertOk();
        $this->assertSame('en', $u->fresh()->locale);
        $this->assertSame('dark', $u->fresh()->theme);
        $this->actingAs($u->fresh())->get('/employees')->assertSee('data-theme="dark"', false)->assertSee('Employees')->assertSee('lang="en"', false);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $u = $this->admin();
        $this->actingAs($u)->postJson('/preferences', ['locale' => 'fr'])->assertStatus(422);
        $this->actingAs($u)->postJson('/preferences', ['theme' => 'pink'])->assertStatus(422);
        $this->assertSame('ar', $u->fresh()->locale);
    }

    public function test_english_pages_have_no_arabic_or_untranslated_keys(): void
    {
        $u = $this->admin();
        $u->forceFill(['locale' => 'en'])->save();
        $company = Company::create(['name' => 'Acme', 'name_en' => 'Acme Co']);
        $emp = Employee::create(['company_id' => $company->id, 'name' => 'Khalid', 'name_en' => 'Khalid', 'status' => 'active']);
        EmployeeDocument::create(['employee_id' => $emp->id, 'type' => 'iqama', 'number' => '1', 'expiry_date' => today()->addDays(10)]);
        EmployeeDocument::create(['employee_id' => $emp->id, 'type' => 'insurance', 'expiry_date' => today()->subDays(2)]);
        $v = Vehicle::create(['company_id' => $company->id, 'plate' => 'ABC 1234', 'status' => 'active', 'make' => 'Toyota', 'driver_id' => $emp->id]);
        VehicleDocument::create(['vehicle_id' => $v->id, 'type' => 'registration', 'expiry_date' => today()->addDays(5)]);
        VehicleRecord::create(['vehicle_id' => $v->id, 'type' => 'fine', 'record_date' => today(), 'amount' => 300]);
        EmployeeRecord::log($emp, 'created', ['company' => 'Acme', 'company_en' => 'Acme Co'], $u->id);
        EmployeeRecord::create(['employee_id' => $emp->id, 'user_id' => $u->id, 'type' => 'warning', 'title' => 'Late', 'event_date' => today()]);
        $emp->leaves()->create(['leave_type_id' => 1, 'start_date' => today(), 'end_date' => today()->addDay(), 'days' => 2, 'status' => 'approved']);

        $paths = ['/', '/tasks', '/employees', '/employees?view=list', '/employees/create', "/employees/{$emp->id}", "/employees/{$emp->id}?tab=leaves", "/employees/{$emp->id}?tab=notes", '/leaves', '/leaves/create',
            '/vehicles', '/vehicles?view=list', '/vehicles/create', "/vehicles/{$v->id}", "/vehicles/{$v->id}?tab=records", '/vehicle-records', '/alerts',
            '/settings/companies', '/settings/companies/create', "/settings/companies/{$company->id}", '/settings/leave-types', '/users', '/users/create', "/users/{$u->id}", '/account',
            '/employees/register', "/employees/{$emp->id}?tab=record", "/employees/{$emp->id}/print"];
        foreach ($paths as $path) {
            $html = $this->actingAs($u->fresh())->get($path)->assertOk()->getContent();
            $this->assertStringNotContainsString('validation.', $html, $path);
            $this->assertStringNotContainsString('types.', strip_tags($html), "raw translation key on {$path}");
            $visible = preg_replace('/<(script|style)\b.*?<\/\1>/s', '', $html);
            $visible = preg_replace('/<span class="av[^"]*"[^>]*>.*?<\/span>/su', '', $visible);
            $visible = preg_replace('/(value|placeholder|title|aria-label)="[^"]*"/u', '', $visible);
            preg_match_all('/[\x{0600}-\x{06FF}]+/u', strip_tags($visible), $m);
            $bad = array_values(array_diff(array_unique($m[0]), ['العربية', 'أحمد']));
            $this->assertSame([], $bad, "Arabic text leaked into English page {$path}: ".implode(' ', $bad));
        }
    }

    public function test_arabic_pages_render_with_data(): void
    {
        $u = $this->admin();
        $company = Company::create(['name' => 'شركة']);
        $emp = Employee::create(['company_id' => $company->id, 'name' => 'خالد', 'status' => 'active']);
        foreach (['/', '/employees', "/employees/{$emp->id}", '/vehicles', '/alerts', '/leaves'] as $p) {
            $this->actingAs($u)->get($p)->assertOk()->assertSee('dir="rtl"', false);
        }
    }
}

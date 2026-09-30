<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
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
        $this->withCookie('locale', 'en')->get('/login')->assertSee('lang="en" dir="ltr"', false)->assertSee('Sign in')->assertDontSee('تسجيل الدخول — ');
    }

    public function test_user_preferences_persist_and_apply(): void
    {
        $u = $this->user();
        $this->actingAs($u)->postJson('/preferences', ['locale' => 'en', 'theme' => 'dark'])->assertOk();
        $this->assertSame('en', $u->fresh()->locale);
        $this->assertSame('dark', $u->fresh()->theme);
        $this->actingAs($u->fresh())->get('/hr')->assertSee('data-theme="dark"', false)->assertSee('Human resources')->assertSee('lang="en"', false);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $u = $this->user();
        $this->actingAs($u)->postJson('/preferences', ['locale' => 'fr'])->assertStatus(422);
        $this->actingAs($u)->postJson('/preferences', ['theme' => 'pink'])->assertStatus(422);
        $this->assertSame('ar', $u->fresh()->locale);
    }

    public function test_english_pages_have_no_untranslated_keys(): void
    {
        $u = $this->user();
        $u->forceFill(['locale' => 'en'])->save();
        foreach (['/', '/hr', '/hr/employees', '/hr/employees/create', '/hr/org', '/hr/leave', '/hr/leave/create', '/hr/attendance', '/hr/departments', '/hr/leave-types', '/users', '/account'] as $path) {
            $html = $this->actingAs($u->fresh())->get($path)->assertOk()->getContent();
            $this->assertStringNotContainsString('validation.', $html, $path);
            // لا نص عربي ظاهر في واجهة الإنجليزية (نتجاهل النصوص داخل وسوم script/style وقيمة البيانات)
            $visible = preg_replace('/<(script|style)\b.*?<\/\1>/s', '', $html);
            $visible = preg_replace('/<span class="av[^"]*"[^>]*>.*?<\/span>/su', '', $visible);
            $visible = preg_replace('/(value|placeholder|title|aria-label)="[^"]*"/u', '', $visible);
            preg_match_all('/[\x{0600}-\x{06FF}]+/u', strip_tags($visible), $m);
            $allowed = ['العربية', $u->name];
            $bad = array_values(array_diff(array_unique($m[0]), $allowed, ['أحمد']));
            $this->assertSame([], $bad, "Arabic text leaked into English page {$path}: ".implode(' ', $bad));
        }
    }

    public function test_dates_follow_locale(): void
    {
        $u = $this->user();
        $this->actingAs($u)->get('/hr')->assertSee(now()->locale('ar')->translatedFormat('l'));
        $u->forceFill(['locale' => 'en'])->save();
        $this->actingAs($u->fresh())->get('/hr')->assertSee(now()->locale('en')->translatedFormat('l'));
    }
}

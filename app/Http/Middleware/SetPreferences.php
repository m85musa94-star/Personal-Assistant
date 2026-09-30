<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/** يحدّد لغة الواجهة ومظهرها: تفضيل المستخدم، ثم الكوكي (للزائر)، ثم العربية/تلقائي. */
class SetPreferences
{
    public const LOCALES = ['ar', 'en'];

    public const THEMES = ['auto', 'light', 'dark'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $locale = $user?->locale ?? $request->cookie('locale');
        $theme = $user?->theme ?? $request->cookie('theme');
        $locale = in_array($locale, self::LOCALES, true) ? $locale : 'ar';
        $theme = in_array($theme, self::THEMES, true) ? $theme : 'auto';

        app()->setLocale($locale);
        View::share(['uiLocale' => $locale, 'uiTheme' => $theme, 'uiDir' => $locale === 'ar' ? 'rtl' : 'ltr']);

        return $next($request);
    }
}

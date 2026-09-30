<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetPreferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/** حفظ اللغة والمظهر: في حساب المستخدم إن كان مسجّلًا، وفي كوكي للزائر (صفحة الدخول). */
class PreferencesController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'locale' => ['nullable', 'in:'.implode(',', SetPreferences::LOCALES)],
            'theme' => ['nullable', 'in:'.implode(',', SetPreferences::THEMES)],
        ]);
        $data = array_filter($data);

        if ($user = $request->user()) {
            $user->forceFill($data)->save();
        }
        foreach ($data as $k => $v) {
            Cookie::queue($k, $v, 60 * 24 * 365);
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true] + $data)
            : back();
    }
}

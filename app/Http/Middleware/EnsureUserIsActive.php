<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** يُخرج المستخدم الموقوف فورًا حتى لو كانت جلسته مفتوحة. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => __('الحساب موقوف')], 401)
                : redirect()->route('login')->withErrors(['email' => __('هذا الحساب موقوف. تواصل مع المدير.')]);
        }

        return $next($request);
    }
}

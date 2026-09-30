<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function show()
    {
        return view('login', ['canSetup' => ! User::query()->exists()]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $email = Str::lower(trim($data['email']));
        $key = 'login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $wait = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['email' => __('محاولات كثيرة. حاول بعد :n ثانية.', ['n' => $wait])]);
        }

        // الاستعلام على بريد مُصغَّر الحروف لأن الإدخال من الجوال كثيرًا ما يبدأ بحرف كبير.
        $ok = Auth::attempt(['email' => $email, 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'));
        if (! $ok) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => __('البريد أو كلمة المرور غير صحيحة، أو الحساب موقوف.')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

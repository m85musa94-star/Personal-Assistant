<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function show()
    {
        return view('account');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ], [
            'current_password.current_password' => __('كلمة المرور الحالية غير صحيحة.'),
            'password.confirmed' => __('تأكيد كلمة المرور غير مطابق.'),
            'password.min' => __('كلمة المرور 10 أحرف على الأقل.'),
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('ok', __('تم تغيير كلمة المرور.'));
    }
}

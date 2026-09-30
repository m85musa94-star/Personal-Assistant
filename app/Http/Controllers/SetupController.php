<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * إنشاء المدير الأول من المتصفح. متاح فقط ما دام لا يوجد أي مستخدم؛
 * وبمجرد إنشاء أول حساب يُغلق نهائيًا (404).
 */
class SetupController extends Controller
{
    public function show()
    {
        abort_if(User::query()->exists(), 404);

        return view('setup');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(User::query()->exists(), 404);

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ], [
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min' => 'كلمة المرور 10 أحرف على الأقل.',
        ]);

        // قفل المعاملة يمنع إنشاء مديرين اثنين لو وصل طلبان معًا.
        $user = DB::transaction(function () use ($data) {
            if (User::query()->lockForUpdate()->exists()) {
                return null;
            }
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->is_admin = true;
            $user->is_active = true;
            $user->save();

            return $user;
        });
        abort_if($user === null, 404);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}

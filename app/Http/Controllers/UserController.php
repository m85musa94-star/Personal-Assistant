<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return view('users', ['users' => User::orderBy('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', Password::min(10)],
            'role' => ['required', 'in:user,hr,admin'],
        ], [
            'email.unique' => __('هذا البريد مسجّل مسبقًا.'),
            'password.min' => __('كلمة المرور 10 أحرف على الأقل.'),
        ]);

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $this->applyRole($user, $data['role']);
        $user->is_active = true;
        $user->save();

        return back()->with('ok', __('تمت إضافة :name.', ['name' => $user->name]));
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['users' => __('لا يمكنك إيقاف حسابك بنفسك.')]);
        }
        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('ok', $user->is_active ? __('تم تفعيل الحساب.') : __('تم إيقاف الحساب.'));
    }

    public function password(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', Password::min(10)]], [
            'password.min' => __('كلمة المرور 10 أحرف على الأقل.'),
        ]);
        $user->update(['password' => $data['password']]);

        return back()->with('ok', __('تم تعيين كلمة مرور جديدة لـ :name.', ['name' => $user->name]));
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['users' => __('لا يمكنك تغيير دور حسابك بنفسك.')]);
        }
        $data = $request->validate(['role' => ['required', 'in:user,hr,admin']]);
        $this->applyRole($user, $data['role']);
        $user->save();

        return back()->with('ok', __('تم تحديث الدور.'));
    }

    private function applyRole(User $user, string $role): void
    {
        $user->is_admin = $role === 'admin';
        $user->is_hr = $role === 'hr';
    }
}

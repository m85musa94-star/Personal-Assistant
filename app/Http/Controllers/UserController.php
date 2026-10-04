<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** إدارة حسابات الدخول وصلاحياتها (لمدير النظام فقط). */
class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::with('companies')->orderBy('id')->get()]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['permissions' => Permissions::PRESETS['viewer']]), 'companies' => Company::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', Password::min(10)],
            ...$this->accessRules(),
        ], [
            'email.unique' => __('هذا البريد مسجّل مسبقًا.'),
            'password.min' => __('كلمة المرور 10 أحرف على الأقل.'),
            'company_ids.required_if' => __('اختر شركة واحدة على الأقل.'),
        ]);

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->is_active = true;
        $this->applyAccess($user, $request);
        $user->save();
        $this->syncCompanies($user, $request);

        return redirect()->route('users.show', $user)->with('ok', __('تمت إضافة :name.', ['name' => $user->name]));
    }

    public function show(User $user)
    {
        $user->load('companies');

        return view('users.form', ['user' => $user, 'companies' => Company::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $self = $user->is($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            ...($self ? [] : $this->accessRules()),
        ], [
            'email.unique' => __('هذا البريد مسجّل مسبقًا.'),
            'company_ids.required_if' => __('اختر شركة واحدة على الأقل.'),
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        // لا يغيّر المدير صلاحياته بنفسه (يبقى مديرًا).
        if (! $self) {
            $this->applyAccess($user, $request);
        }
        $user->save();
        if (! $self) {
            $this->syncCompanies($user, $request);
        }

        return redirect()->route('users.show', $user)->with('ok', __('تم حفظ التعديلات.'));
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

    private function accessRules(): array
    {
        return [
            'is_admin' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Permissions::all())],
            'company_scope' => ['required', 'in:all,selected'],
            'company_ids' => ['required_if:company_scope,selected', 'nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
        ];
    }

    private function applyAccess(User $user, Request $request): void
    {
        $user->is_admin = $request->boolean('is_admin');
        $user->permissions = $user->is_admin ? null : Permissions::normalize((array) $request->input('permissions', []));
        $user->all_companies = $user->is_admin || $request->input('company_scope') !== 'selected';
    }

    private function syncCompanies(User $user, Request $request): void
    {
        $user->companies()->sync($user->all_companies ? [] : array_map('intval', (array) $request->input('company_ids', [])));
    }
}

@extends('layouts.shell')
@section('title', __('حسابات الدخول').' — '.__('مركز القيادة'))
@section('content')
<div class="page-h"><h1>{{ __('حسابات الدخول') }}</h1></div>
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="card">
  <div class="tbl-wrap"><table>
    <tr><th>{{ __('الاسم') }}</th><th>{{ __('البريد الإلكتروني') }}</th><th>{{ __('الدور') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('إجراءات') }}</th></tr>
    @foreach($users as $u)
    <tr>
      <td><b>{{ $u->name }}</b></td>
      <td dir="ltr" style="text-align:start">{{ $u->email }}</td>
      <td>
        <span class="pill {{ $u->is_admin ? 'vio' : ($u->is_hr ? 'blu' : '') }}">{{ $u->is_admin ? __('مدير النظام') : ($u->is_hr ? __('مسؤول الموارد البشرية') : __('مستخدم')) }}</span>
      </td>
      <td><span class="pill {{ $u->is_active ? 'grn' : 'red' }}">{{ $u->is_active ? __('نشط') : __('موقوف') }}</span></td>
      <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
        <form method="POST" action="{{ route('users.toggle', $u) }}">@csrf
          <button class="btn sm sec" @disabled($u->is(auth()->user()))>{{ $u->is_active ? __('إيقاف') : __('تفعيل') }}</button>
        </form>
        <form method="POST" action="{{ route('users.role', $u) }}">@csrf @method('PUT')
          <select name="role" onchange="this.form.submit()" @disabled($u->is(auth()->user())) aria-label="{{ __('الدور') }}">
            <option value="user" @selected(! $u->is_admin && ! $u->is_hr)>{{ __('مستخدم') }}</option>
            <option value="hr" @selected(! $u->is_admin && $u->is_hr)>{{ __('مسؤول الموارد البشرية') }}</option>
            <option value="admin" @selected($u->is_admin)>{{ __('مدير النظام') }}</option>
          </select>
        </form>
        <form method="POST" action="{{ route('users.password', $u) }}" style="display:flex;gap:4px">@csrf @method('PUT')
          <input type="password" name="password" placeholder="{{ __('كلمة مرور جديدة') }}" minlength="10" required dir="ltr" style="width:150px">
          <button class="btn sm">{{ __('تعيين') }}</button>
        </form>
        </div>
      </td>
    </tr>
    @endforeach
  </table></div>
</div>
<div class="card" style="margin-top:16px">
  <h3>{{ __('إضافة مستخدم') }}</h3>
  <form method="POST" action="{{ route('users.store') }}" class="f">
    @csrf
    <label>{{ __('الاسم') }}<input name="name" required value="{{ old('name') }}"></label>
    <label>{{ __('البريد الإلكتروني') }}<input type="email" name="email" required value="{{ old('email') }}" dir="ltr"></label>
    <label>{{ __('كلمة المرور (10 أحرف فأكثر)') }}<input type="password" name="password" required minlength="10" autocomplete="new-password" dir="ltr"></label>
    <label>{{ __('الدور') }}
      <select name="role"><option value="user">{{ __('مستخدم') }}</option><option value="hr">{{ __('مسؤول الموارد البشرية') }}</option><option value="admin">{{ __('مدير النظام') }}</option></select>
    </label>
    <button class="btn w">{{ __('إضافة') }}</button>
  </form>
</div>
@endsection

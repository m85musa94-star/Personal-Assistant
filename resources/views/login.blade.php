@extends('layouts.auth')
@section('title', __('تسجيل الدخول').' — '.__('مركز القيادة'))
@section('content')
<div class="card">
  <h1>{{ __('تسجيل الدخول') }}</h1>
  <p class="sub">{{ __('سجّل الدخول للوصول إلى مهامك من أي جهاز') }}</p>
  <form method="POST" action="{{ route('login.post') }}" class="f one">
    @csrf
    <label>{{ __('البريد الإلكتروني') }}
      <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" dir="ltr" inputmode="email" autocapitalize="none">
    </label>
    <label>{{ __('كلمة المرور') }}
      <input type="password" name="password" required autocomplete="current-password" dir="ltr">
    </label>
    <label class="chkrow"><input type="checkbox" name="remember" value="1" checked> {{ __('تذكّرني على هذا الجهاز') }}</label>
    @error('email')<div class="err">{{ $message }}</div>@enderror
    @error('password')<div class="err">{{ $message }}</div>@enderror
    <button class="btn block">{{ __('تسجيل الدخول') }}</button>
  </form>
  @if($canSetup)
    <p style="text-align:center;margin:16px 0 0"><a class="btn sec block" href="{{ route('setup') }}">{{ __('لا يوجد أي حساب بعد — أنشئ حساب المدير الأول') }}</a></p>
  @endif
  <p class="sub" style="margin:16px 0 0">{{ __('لا يوجد تسجيل ذاتي. يضيف المدير الحسابات، ولنسيان كلمة المرور تواصل معه.') }}</p>
</div>
@endsection

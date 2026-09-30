@extends('layouts.plain')
@section('title', 'تسجيل الدخول — مركز القيادة')
@section('content')
<div class="card">
  <h2 style="text-align:center">🧭 مركز القيادة</h2>
  <p class="date" style="text-align:center">سجّل الدخول للوصول إلى مهامك من أي جهاز</p>
  <form method="POST" action="{{ route('login.post') }}" class="f" style="grid-template-columns:1fr">
    @csrf
    <label>البريد الإلكتروني
      <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" dir="ltr" inputmode="email" autocapitalize="none">
    </label>
    <label>كلمة المرور
      <input type="password" name="password" required autocomplete="current-password" dir="ltr">
    </label>
    <label style="flex-direction:row;align-items:center;gap:6px"><input type="checkbox" name="remember" value="1" checked> تذكّرني على هذا الجهاز</label>
    @error('email')<div class="err">{{ $message }}</div>@enderror
    @error('password')<div class="err">{{ $message }}</div>@enderror
    <button class="btn">تسجيل الدخول</button>
  </form>
  <p class="date" style="text-align:center;margin-bottom:0">لا يوجد تسجيل ذاتي. يضيف المدير الحسابات، ولنسيان كلمة المرور تواصل معه.</p>
</div>
@endsection

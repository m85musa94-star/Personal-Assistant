@extends('layouts.plain')
@section('title', 'إعداد مركز القيادة')
@section('content')
<div class="card">
  <h2 style="text-align:center">🧭 إعداد أول مدير</h2>
  <p class="date" style="text-align:center">هذه الصفحة تظهر مرة واحدة فقط وتُغلق بعد إنشاء الحساب.</p>
  <form method="POST" action="{{ route('setup.store') }}" class="f" style="grid-template-columns:1fr">
    @csrf
    <label>الاسم<input name="name" value="{{ old('name') }}" required autofocus></label>
    <label>البريد الإلكتروني<input type="email" name="email" value="{{ old('email') }}" required dir="ltr" inputmode="email" autocapitalize="none" autocomplete="username"></label>
    <label>كلمة المرور (10 أحرف فأكثر، واكتبها في مكان آمن)<input type="password" name="password" required minlength="10" dir="ltr" autocomplete="new-password"></label>
    <label>تأكيد كلمة المرور<input type="password" name="password_confirmation" required minlength="10" dir="ltr" autocomplete="new-password"></label>
    @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
    <button class="btn">إنشاء الحساب والدخول</button>
  </form>
</div>
@endsection

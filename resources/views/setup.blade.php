@extends('layouts.auth')
@section('title', __('إعداد أول مدير').' — '.__('مركز القيادة'))
@section('content')
<div class="card">
  <h1>{{ __('إعداد أول مدير') }}</h1>
  <p class="sub">{{ __('هذه الصفحة تظهر مرة واحدة فقط وتُغلق بعد إنشاء الحساب.') }}</p>
  <form method="POST" action="{{ route('setup.store') }}" class="f one">
    @csrf
    <label>{{ __('الاسم') }}<input name="name" value="{{ old('name') }}" required autofocus></label>
    <label>{{ __('البريد الإلكتروني') }}<input type="email" name="email" value="{{ old('email') }}" required dir="ltr" inputmode="email" autocapitalize="none" autocomplete="username"></label>
    <label>{{ __('كلمة المرور (10 أحرف فأكثر، واكتبها في مكان آمن)') }}<input type="password" name="password" required minlength="10" dir="ltr" autocomplete="new-password"></label>
    <label>{{ __('تأكيد كلمة المرور') }}<input type="password" name="password_confirmation" required minlength="10" dir="ltr" autocomplete="new-password"></label>
    @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
    <button class="btn block">{{ __('إنشاء الحساب والدخول') }}</button>
  </form>
</div>
@endsection

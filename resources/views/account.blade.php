@extends('layouts.odoo')
@section('title', __('حسابي').' — '.__('مركز القيادة'))
@section('controlpanel')<x-cp :crumbs="[[__('حسابي'), null]]"></x-cp>@endsection
@section('content')
<div class="grid g2">
  <div class="card">
    <div class="ecard"><span class="av lg">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
      <div class="ttl"><b style="font-size:18px">{{ auth()->user()->name }}</b><small dir="ltr">{{ auth()->user()->email }}</small></div></div>
    <p class="sub" style="margin-top:14px">{{ auth()->user()->is_admin ? __('مدير النظام') : (auth()->user()->is_hr ? __('مسؤول الموظفين والمركبات') : __('مستخدم (عرض فقط)')) }}</p>
    <p class="sub">{{ __('اللغة والمظهر من أزرار الشريط العلوي، ويُحفظان في حسابك.') }}</p>
  </div>
  <div class="card">
    <h3>{{ __('تغيير كلمة المرور') }}</h3>
    <form method="POST" action="{{ route('account.password') }}" class="f one">
      @csrf @method('PUT')
      <label>{{ __('كلمة المرور الحالية') }}<input type="password" name="current_password" required autocomplete="current-password" dir="ltr"></label>
      <label>{{ __('كلمة المرور الجديدة (10 أحرف فأكثر)') }}<input type="password" name="password" required minlength="10" autocomplete="new-password" dir="ltr"></label>
      <label>{{ __('تأكيد كلمة المرور الجديدة') }}<input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password" dir="ltr"></label>
      @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
      <button class="btn">{{ __('حفظ') }}</button>
    </form>
  </div>
</div>
@endsection

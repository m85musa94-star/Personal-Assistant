@extends('layouts.plain')
@section('title', 'حسابي — مركز القيادة')
@section('content')
<p><a href="{{ route('home') }}">← العودة للتطبيق</a></p>
<div class="card">
  <h2>حسابي</h2>
  <p>{{ auth()->user()->name }} — <span dir="ltr">{{ auth()->user()->email }}</span></p>
  @if(session('ok'))<div class="okm">{{ session('ok') }}</div>@endif
  <h3>تغيير كلمة المرور</h3>
  <form method="POST" action="{{ route('account.password') }}" class="f" style="grid-template-columns:1fr">
    @csrf @method('PUT')
    <label>كلمة المرور الحالية<input type="password" name="current_password" required autocomplete="current-password" dir="ltr"></label>
    <label>كلمة المرور الجديدة (10 أحرف فأكثر)<input type="password" name="password" required minlength="10" autocomplete="new-password" dir="ltr"></label>
    <label>تأكيد كلمة المرور الجديدة<input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password" dir="ltr"></label>
    @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
    <button class="btn">حفظ</button>
  </form>
</div>
@endsection

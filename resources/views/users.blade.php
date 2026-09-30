@extends('layouts.plain')
@section('title', 'المستخدمون — مركز القيادة')
@section('wide', 'wide')
@section('content')
<p><a href="{{ route('home') }}">← العودة للتطبيق</a></p>
<div class="card">
  <h2>المستخدمون</h2>
  @if(session('ok'))<div class="okm">{{ session('ok') }}</div>@endif
  @foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
  <div style="overflow-x:auto">
  <table>
    <tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>إجراءات</th></tr>
    @foreach($users as $u)
    <tr>
      <td>{{ $u->name }}</td>
      <td dir="ltr" style="text-align:right">{{ $u->email }}</td>
      <td>{{ $u->is_admin ? 'مدير' : 'مستخدم' }}</td>
      <td>{{ $u->is_active ? 'نشط' : 'موقوف' }}</td>
      <td>
        <form method="POST" action="{{ route('users.toggle', $u) }}" style="display:inline">@csrf
          <button class="btn sm sec" @disabled($u->is(auth()->user()))>{{ $u->is_active ? 'إيقاف' : 'تفعيل' }}</button>
        </form>
        <form method="POST" action="{{ route('users.password', $u) }}" style="display:inline-flex;gap:4px;margin-top:4px">@csrf @method('PUT')
          <input type="password" name="password" placeholder="كلمة مرور جديدة" minlength="10" required dir="ltr" style="width:150px">
          <button class="btn sm">تعيين</button>
        </form>
      </td>
    </tr>
    @endforeach
  </table>
  </div>
</div>
<div class="card" style="margin-top:14px">
  <h3>إضافة مستخدم</h3>
  <form method="POST" action="{{ route('users.store') }}" class="f">
    @csrf
    <label>الاسم<input name="name" required value="{{ old('name') }}"></label>
    <label>البريد الإلكتروني<input type="email" name="email" required value="{{ old('email') }}" dir="ltr"></label>
    <label>كلمة المرور (10 أحرف فأكثر)<input type="password" name="password" required minlength="10" autocomplete="new-password" dir="ltr"></label>
    <label style="flex-direction:row;align-items:center;gap:6px;align-self:end"><input type="checkbox" name="is_admin" value="1"> مدير (يدير المستخدمين)</label>
    <button class="btn w">إضافة</button>
  </form>
</div>
@endsection

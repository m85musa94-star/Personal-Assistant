@extends('layouts.shell')
@section('title', __('الأقسام').' — '.__('مركز القيادة'))
@section('content')
<div class="page-h"><h1>{{ __('الأقسام') }}</h1></div>
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="card">
  @forelse($departments as $d)
    <form method="POST" action="{{ route('hr.departments.update', $d) }}" class="row static" style="flex-wrap:wrap">
      @csrf @method('PUT')
      <input name="name" value="{{ $d->name }}" required aria-label="{{ __('الاسم (عربي)') }}" style="flex:1;min-width:160px">
      <input name="name_en" value="{{ $d->name_en }}" dir="ltr" placeholder="English" aria-label="{{ __('الاسم (إنجليزي)') }}" style="flex:1;min-width:160px">
      <span class="pill">{{ $d->employees_count }} {{ __('موظف') }}</span>
      <button class="btn sm">{{ __('حفظ') }}</button>
      <button class="btn sm sec" form="del-{{ $d->id }}">{{ __('حذف') }}</button>
    </form>
    <form id="del-{{ $d->id }}" method="POST" action="{{ route('hr.departments.destroy', $d) }}" onsubmit="return confirm('{{ __('حذف القسم؟') }}')">@csrf @method('DELETE')</form>
  @empty <div class="empty">{{ __('لا أقسام بعد. أضف أول قسم بالأسفل.') }}</div> @endforelse
</div>
<div class="card" style="margin-top:16px"><h3>{{ __('إضافة قسم') }}</h3>
  <form method="POST" action="{{ route('hr.departments.store') }}" class="f">@csrf
    <label>{{ __('الاسم (عربي)') }}<input name="name" required></label>
    <label>{{ __('الاسم (إنجليزي)') }}<input name="name_en" dir="ltr"></label>
    <button class="btn w">{{ __('إضافة') }}</button></form></div>
@endsection

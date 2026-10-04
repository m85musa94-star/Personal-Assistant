@extends('layouts.odoo')
@section('title', __('أنواع الإجازات').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('أنواع الإجازات'), null]]"></x-cp>
@endsection
@section('content')
@php $dis = ! auth()->user()->can('leave_types.edit'); @endphp
<p class="sub">{{ __('الاستحقاق السنوي يُدخله المسؤول حسب نظام الشركة وعقودها. اتركه فارغًا إن لم يُحدَّد؛ عندها لا يُحتسب رصيد ولا تُفترض أرقام.') }}</p>
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="card">
  @foreach($types as $t)
    <form method="POST" action="{{ route('leave-types.update', $t) }}" class="row static" style="flex-wrap:wrap">
      @csrf @method('PUT')
      <input name="name" value="{{ $t->name }}" required style="flex:1;min-width:140px" aria-label="{{ __('الاسم (عربي)') }}" @disabled($dis)>
      <input name="name_en" value="{{ $t->name_en }}" dir="ltr" style="flex:1;min-width:140px" aria-label="{{ __('الاسم (إنجليزي)') }}" @disabled($dis)>
      <input type="number" step="0.5" min="0" max="365" name="annual_days" value="{{ $t->annual_days }}" placeholder="{{ __('أيام/سنة') }}" style="width:110px" dir="ltr" aria-label="{{ __('الاستحقاق السنوي (أيام)') }}" @disabled($dis)>
      <label class="chkrow"><input type="checkbox" name="is_paid" value="1" @checked($t->is_paid) @disabled($dis)> {{ __('مدفوعة') }}</label>
      <label class="chkrow"><input type="checkbox" name="is_active" value="1" @checked($t->is_active) @disabled($dis)> {{ __('مفعّلة') }}</label>
      <span class="pill">{{ $t->requests_count }} {{ __('سجل') }}</span>
      @unless($dis)<button class="btn sm">{{ __('حفظ') }}</button><button class="btn sm sec" form="lt-{{ $t->id }}">{{ __('حذف') }}</button>@endunless
    </form>
    <form id="lt-{{ $t->id }}" method="POST" action="{{ route('leave-types.destroy', $t) }}" onsubmit="return confirm('{{ __('حذف هذا النوع؟') }}')">@csrf @method('DELETE')</form>
  @endforeach
</div>
@unless($dis)
<div class="card" style="margin-top:16px"><h3>{{ __('إضافة نوع') }}</h3>
  <form method="POST" action="{{ route('leave-types.store') }}" class="f">@csrf
    <label>{{ __('الاسم (عربي)') }}<input name="name" required></label>
    <label>{{ __('الاسم (إنجليزي)') }}<input name="name_en" dir="ltr"></label>
    <label>{{ __('الاستحقاق السنوي (أيام)') }}<input type="number" step="0.5" min="0" max="365" name="annual_days" dir="ltr"></label>
    <div style="display:flex;gap:14px;align-items:end"><label class="chkrow"><input type="checkbox" name="is_paid" value="1" checked> {{ __('مدفوعة') }}</label><label class="chkrow"><input type="checkbox" name="is_active" value="1" checked> {{ __('مفعّلة') }}</label></div>
    <button class="btn w">{{ __('إضافة') }}</button></form></div>
@endunless
@endsection

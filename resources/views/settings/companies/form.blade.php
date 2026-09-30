@extends('layouts.odoo')
@php $isNew = ! $company->exists; $dis = ! auth()->user()->canManageData(); @endphp
@section('title', ($isNew ? __('شركة جديدة') : $company->displayName()).' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('الشركات'), route('companies.index')], [$isNew ? __('جديد') : $company->displayName(), null]]">
  <x-slot:actions>@unless($dis)<button form="cf" class="btn">{{ __('حفظ') }}</button><a class="btn sec" href="{{ route('companies.index') }}">{{ __('إلغاء') }}</a>@endunless</x-slot:actions>
</x-cp>
@endsection
@section('content')
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="o-sheet">
  @unless($isNew)
    <div class="o-smart">
      <a href="{{ route('employees.index') }}" onclick="event.preventDefault();document.getElementById('sw-{{ $company->id }}-e').submit()"><x-icon name="users"/><span><b>{{ $company->employees_count }}</b><small>{{ __('الموظفون') }}</small></span></a>
      <a href="{{ route('vehicles.index') }}" onclick="event.preventDefault();document.getElementById('sw-{{ $company->id }}-v').submit()"><x-icon name="car"/><span><b>{{ $company->vehicles_count }}</b><small>{{ __('المركبات') }}</small></span></a>
    </div>
    @foreach(['e' => 'employees.index', 'v' => 'vehicles.index'] as $k => $r)
      <form id="sw-{{ $company->id }}-{{ $k }}" method="POST" action="{{ route('company.switch') }}" hidden>@csrf<input type="hidden" name="company" value="{{ $company->id }}"></form>
    @endforeach
  @endunless
  <form id="cf" method="POST" action="{{ $isNew ? route('companies.store') : route('companies.update', $company) }}">
    @csrf @unless($isNew) @method('PUT') @endunless
    <div class="o-title"><label>{{ __('اسم الشركة') }}</label><input name="name" value="{{ old('name', $company->name) }}" required @disabled($dis)></div>
    <div class="o-fields">
      <div class="o-f"><label>{{ __('الاسم (إنجليزي)') }}</label><input name="name_en" value="{{ old('name_en', $company->name_en) }}" dir="ltr" @disabled($dis)></div>
      <div class="o-f"><label>{{ __('السجل التجاري') }}</label><input name="cr_number" value="{{ old('cr_number', $company->cr_number) }}" dir="ltr" @disabled($dis)></div>
      <div class="o-f"><label>{{ __('الرقم الضريبي') }}</label><input name="tax_number" value="{{ old('tax_number', $company->tax_number) }}" dir="ltr" @disabled($dis)></div>
      <div class="o-f"><label>{{ __('الجوال') }}</label><input name="phone" value="{{ old('phone', $company->phone) }}" dir="ltr" @disabled($dis)></div>
      <div class="o-f"><label>{{ __('البريد الإلكتروني') }}</label><input type="email" name="email" value="{{ old('email', $company->email) }}" dir="ltr" @disabled($dis)></div>
      <div class="o-f"><label>{{ __('العنوان') }}</label><input name="address" value="{{ old('address', $company->address) }}" @disabled($dis)></div>
      <div class="o-f"><label>{{ __('نشطة') }}</label><span><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $company->is_active)) @disabled($dis)></span></div>
      <div class="o-f full"><label>{{ __('ملاحظات') }}</label><textarea name="notes" rows="3" @disabled($dis)>{{ old('notes', $company->notes) }}</textarea></div>
    </div>
  </form>
  @if(! $isNew && auth()->user()->is_admin)
    <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('{{ __('حذف الشركة؟') }}')" style="margin-top:24px">@csrf @method('DELETE')<button class="btn sec sm"><x-icon name="trash"/> {{ __('حذف الشركة') }}</button></form>
  @endif
</div>
@endsection

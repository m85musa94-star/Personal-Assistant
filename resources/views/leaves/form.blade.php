@extends('layouts.odoo')
@section('title', __('تسجيل إجازة').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('الإجازات'), route('leaves.index')], [__('تسجيل إجازة'), null]]">
  <x-slot:actions><button form="lf" class="btn">{{ __('حفظ') }}</button><a class="btn sec" href="{{ route('leaves.index') }}">{{ __('إلغاء') }}</a></x-slot:actions>
</x-cp>
@endsection
@section('content')
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<form id="lf" method="POST" action="{{ route('leaves.store') }}" class="o-sheet">
  @csrf
  <div class="o-fields">
    <div class="o-f"><label>{{ __('الموظف') }}</label><select name="employee_id" required>@foreach($employees as $e)<option value="{{ $e->id }}" @selected((string) old('employee_id', $employeeId) === (string) $e->id)>{{ $e->displayName() }} — {{ $e->company?->displayName() }}</option>@endforeach</select></div>
    <div class="o-f"><label>{{ __('نوع الإجازة') }}</label><select name="leave_type_id" required>@foreach($types as $t)<option value="{{ $t->id }}" @selected((string) old('leave_type_id') === (string) $t->id)>{{ $t->displayName() }}</option>@endforeach</select></div>
    <div class="o-f"><label>{{ __('من') }}</label><input type="date" name="start_date" value="{{ old('start_date') }}" required></div>
    <div class="o-f"><label>{{ __('إلى') }}</label><input type="date" name="end_date" value="{{ old('end_date') }}" required></div>
    <div class="o-f"><label>{{ __('عدد الأيام') }}</label><input type="number" step="0.5" min="0.5" name="days" value="{{ old('days') }}" dir="ltr"></div>
    <div class="sub" style="align-self:center;margin:0">{{ __('يُحتسب تلقائيًا بالأيام التقويمية (شاملة الجمعة والسبت والعطلات). عدّله يدويًا إن لزم.') }}</div>
    <div class="o-f full"><label>{{ __('ملاحظات') }}</label><textarea name="reason" rows="3">{{ old('reason') }}</textarea></div>
  </div>
</form>
@include('partials.leave-days-js')
@endsection

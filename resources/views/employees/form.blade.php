@extends('layouts.odoo')
@section('title', __('موظف جديد').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('الموظفون'), route('employees.index')], [__('جديد'), null]]">
  <x-slot:actions><button form="ef" class="btn">{{ __('حفظ') }}</button><a class="btn sec" href="{{ route('employees.index') }}">{{ __('إلغاء') }}</a></x-slot:actions>
</x-cp>
@endsection
@section('content')
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<form id="ef" method="POST" action="{{ route('employees.store') }}" class="o-sheet">
  @csrf
  <input type="hidden" name="status" value="active">
  @include('employees._fields')
  <div class="o-f full" style="margin-top:14px"><label>{{ __('ملاحظات') }}</label><textarea name="notes" rows="3">{{ old('notes') }}</textarea></div>
  <p class="sub" style="margin-top:18px">{{ __('بعد الحفظ تضيف الإقامة والتأمين والعقد وغيرها من تبويب «الوثائق».') }}</p>
</form>
@endsection

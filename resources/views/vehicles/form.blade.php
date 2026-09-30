@extends('layouts.odoo')
@section('title', __('مركبة جديدة').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('المركبات'), route('vehicles.index')], [__('جديد'), null]]">
  <x-slot:actions><button form="vf" class="btn">{{ __('حفظ') }}</button><a class="btn sec" href="{{ route('vehicles.index') }}">{{ __('إلغاء') }}</a></x-slot:actions>
</x-cp>
@endsection
@section('content')
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<form id="vf" method="POST" action="{{ route('vehicles.store') }}" class="o-sheet">
  @csrf
  <input type="hidden" name="status" value="active">
  @include('vehicles._fields')
  <div class="o-f full" style="margin-top:14px"><label>{{ __('ملاحظات') }}</label><textarea name="notes" rows="3">{{ old('notes') }}</textarea></div>
  <p class="sub" style="margin-top:18px">{{ __('بعد الحفظ تضيف الاستمارة والتأمين والفحص الدوري وسجلات الصيانة والمخالفات.') }}</p>
</form>
@endsection

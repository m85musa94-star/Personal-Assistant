@extends('layouts.odoo')
@section('title', __('سجلات المركبات').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('السجلات'), null]]">
  <x-slot:right><span class="date">{{ $records->total() }} {{ __('سجل') }} · {{ __('الإجمالي') }}: <b>{{ number_format((float) $total, 2) }}</b></span></x-slot:right>
  <x-slot:search>
    <form method="GET" action="{{ route('vehicle-records.index') }}" class="o-fsel" style="flex:1">
      <select name="vehicle" onchange="this.form.submit()" aria-label="{{ __('المركبة') }}"><option value="">{{ __('كل المركبات') }}</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected((string) $vehicleId === (string) $v->id)>{{ $v->plate }}</option>@endforeach</select>
      <select name="type" onchange="this.form.submit()" aria-label="{{ __('النوع') }}"><option value="">{{ __('كل الأنواع') }}</option>@foreach(\App\Models\VehicleRecord::TYPES as $t)<option value="{{ $t }}" @selected($type === $t)>{{ __('types.vehicle_record.'.$t) }}</option>@endforeach</select>
      <input type="number" name="year" value="{{ $year }}" min="2000" max="2100" placeholder="{{ __('السنة') }}" style="width:100px;border-radius:999px;height:38px" onchange="this.form.submit()" aria-label="{{ __('السنة') }}">
    </form>
  </x-slot:search>
</x-cp>
@endsection
@section('content')
@php $cc = \App\Support\CompanyContext::current(); @endphp
<div class="o-list tbl-wrap">
  @if($records->isEmpty())<div class="empty">{{ __('لا سجلات بعد.') }}</div>@else
  <table><thead><tr><th>{{ __('التاريخ') }}</th><th>{{ __('المركبة') }}</th>@unless($cc)<th>{{ __('الشركة') }}</th>@endunless<th>{{ __('النوع') }}</th><th>{{ __('الوصف') }}</th><th>{{ __('الجهة / الورشة') }}</th><th>{{ __('العداد (كم)') }}</th><th>{{ __('المبلغ') }}</th></tr></thead><tbody>
    @foreach($records as $r)
      <tr class="link" onclick="location='{{ route('vehicles.show', ['vehicle' => $r->vehicle, 'tab' => 'records']) }}'">
        <td>{{ $r->record_date->fmt() }}</td><td><span class="plate">{{ $r->vehicle->plate }}</span></td>@unless($cc)<td>{{ $r->vehicle->company?->displayName() }}</td>@endunless
        <td><span class="pill {{ in_array($r->type, ['fine', 'accident']) ? 'red' : 'blu' }}">{{ __('types.vehicle_record.'.$r->type) }}</span></td><td>{{ $r->description }}</td><td>{{ $r->vendor ?: '—' }}</td><td>{{ $r->odometer !== null ? number_format($r->odometer) : '—' }}</td><td>{{ $r->amount !== null ? number_format((float) $r->amount, 2) : '—' }}</td>
      </tr>
    @endforeach
  </tbody></table>@endif
</div>
<div style="margin-top:14px">{{ $records->links() }}</div>
@endsection

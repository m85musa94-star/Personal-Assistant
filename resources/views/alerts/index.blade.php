@extends('layouts.odoo')
@section('title', __('التنبيهات').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('التنبيهات'), null]]">
  <x-slot:right><span class="date">{{ $items->count() }} {{ __('وثيقة') }}</span></x-slot:right>
  <x-slot:search>
    <form method="GET" action="{{ route('alerts.index') }}" class="o-fsel" style="flex:1">
      <select name="scope" onchange="this.form.submit()" aria-label="{{ __('النطاق') }}"><option value="all" @selected($scope === 'all')>{{ __('الموظفون والمركبات') }}</option><option value="employees" @selected($scope === 'employees')>{{ __('الموظفون') }}</option><option value="vehicles" @selected($scope === 'vehicles')>{{ __('المركبات') }}</option></select>
      <select name="days" onchange="this.form.submit()" aria-label="{{ __('المدة') }}">
        <option value="0" @selected($days === 0)>{{ __('المنتهية فقط') }}</option><option value="30" @selected($days === 30)>{{ __('خلال 30 يومًا') }}</option><option value="60" @selected($days === 60)>{{ __('خلال 60 يومًا') }}</option><option value="90" @selected($days === 90)>{{ __('خلال 90 يومًا') }}</option><option value="180" @selected($days === 180)>{{ __('خلال 180 يومًا') }}</option>
      </select>
    </form>
  </x-slot:search>
</x-cp>
@endsection
@section('content')
@php $cc = \App\Support\CompanyContext::current(); @endphp
<div class="o-list tbl-wrap">
  @if($items->isEmpty())<div class="empty"><x-icon name="check-circle"/> {{ __('لا وثائق منتهية أو قريبة من الانتهاء.') }}</div>@else
  <table><thead><tr><th></th><th>{{ __('الجهة') }}</th>@unless($cc)<th>{{ __('الشركة') }}</th>@endunless<th>{{ __('الوثيقة') }}</th><th>{{ __('الرقم') }}</th><th>{{ __('الجهة / الشركة') }}</th><th>{{ __('الانتهاء') }}</th></tr></thead><tbody>
    @foreach($items as $i)
      <tr class="link" onclick="location='{{ $i['url'] }}'">
        <td><x-icon name="{{ $i['kind'] === 'employee' ? 'users' : 'car' }}"/></td><td><a href="{{ $i['url'] }}"><b>{{ $i['owner'] }}</b></a></td>
        @unless($cc)<td>{{ $i['company']?->displayName() }}</td>@endunless
        <td>{{ $i['doc']->label() }}</td><td dir="ltr" style="text-align:start">{{ $i['doc']->number ?: '—' }}</td><td>{{ $i['doc']->provider ?: '—' }}</td><td><x-expiry :doc="$i['doc']"/></td>
      </tr>
    @endforeach
  </tbody></table>@endif
</div>
@endsection

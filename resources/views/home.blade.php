@extends('layouts.odoo')
@section('title', __('مركز القيادة'))
@section('content')
@php $apps = \App\Support\Apps::all(auth()->user()); $cc = \App\Support\CompanyContext::current(); @endphp
<div class="o-home">
  @foreach($apps as $key => $a)
    <a class="o-app" href="{{ $a['url'] }}">
      <span class="tile" style="background:{{ $a['color'] }}"><x-icon name="{{ $a['icon'] }}"/></span>
      {{ $a['label'] }}
      @if($key === 'alerts' && $alerts['total'])<span class="o-badge {{ $alerts['expired'] ? 'red' : '' }}">{{ $alerts['total'] }}</span>@endif
    </a>
  @endforeach
</div>

<div style="max-width:980px;margin:34px auto 0">
  <p class="sub" style="text-align:center">{{ $cc ? __('الشركة الحالية:').' '.$cc->displayName() : __('كل الشركات') }}</p>
  <div class="grid g4">
    <a class="card kpi" href="{{ route('employees.index') }}" style="color:inherit;text-decoration:none"><span class="ic"><x-icon name="users"/></span><div><b>{{ $employees }}</b><span>{{ __('موظف نشط') }}</span></div></a>
    <a class="card kpi" href="{{ route('leaves.index') }}" style="color:inherit;text-decoration:none"><span class="ic vio"><x-icon name="calendar-off"/></span><div><b>{{ $onLeave }}</b><span>{{ __('في إجازة اليوم') }}</span></div></a>
    <a class="card kpi" href="{{ route('vehicles.index') }}" style="color:inherit;text-decoration:none"><span class="ic blu"><x-icon name="car"/></span><div><b>{{ $vehicles }}</b><span>{{ __('مركبة') }}</span></div></a>
    <a class="card kpi" href="{{ route('alerts.index') }}" style="color:inherit;text-decoration:none"><span class="ic {{ $alerts['expired'] ? 'red' : 'amb' }}"><x-icon name="bell"/></span><div><b>{{ $alerts['total'] }}</b><span>{{ __('تنبيه وثائق') }}@if($alerts['expired']) · {{ __(':n منتهية', ['n' => $alerts['expired']]) }}@endif</span></div></a>
  </div>

  @if($urgent->isNotEmpty())
  <div class="card" style="margin-top:16px">
    <h3><x-icon name="alert"/> {{ __('أقرب الوثائق انتهاءً') }}</h3>
    @foreach($urgent as $i)
      <a class="row static" href="{{ $i['url'] }}" style="color:inherit;text-decoration:none">
        <x-icon name="{{ $i['icon'] }}"/>
        <div class="ttl"><b>{{ $i['owner'] }}</b><div class="meta"><span class="tag">{{ $i['doc']->label() }}</span>@if($i['doc']->number)<span class="tag" dir="ltr">{{ $i['doc']->number }}</span>@endif</div></div>
        <x-expiry :doc="$i['doc']"/>
      </a>
    @endforeach
    <p style="margin:12px 0 0"><a href="{{ route('alerts.index') }}">{{ __('عرض كل التنبيهات') }}</a></p>
  </div>
  @elseif(\App\Models\Company::count() === 0)
  <div class="card" style="margin-top:16px;text-align:center">
    <h3>{{ __('ابدأ بإضافة شركتك الأولى') }}</h3>
    <p class="sub">{{ __('الموظفون والمركبات تُسجَّل تحت كل شركة. أضف شركة ثم موظفيها ومركباتها.') }}</p>
    <a class="btn" href="{{ route('companies.create') }}"><x-icon name="plus"/> {{ __('شركة جديدة') }}</a>
  </div>
  @endif
</div>
@endsection

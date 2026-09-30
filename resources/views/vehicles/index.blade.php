@extends('layouts.odoo')
@section('title', __('المركبات').' — '.__('مركز القيادة'))
@section('controlpanel')
@php
    $can = auth()->user()->canManageData(); $cc = \App\Support\CompanyContext::current();
    $qs = fn (array $o) => route('vehicles.index', array_filter(array_merge(['q' => $q, 'status' => $status, 'view' => $view, 'alerts' => $onlyAlerts ? 1 : null], $o), fn ($v) => $v !== null && $v !== ''));
@endphp
<x-cp :crumbs="[[__('المركبات'), null]]">
  <x-slot:actions>@if($can)<a class="btn" href="{{ route('vehicles.create') }}"><x-icon name="plus"/> {{ __('جديد') }}</a>@endif</x-slot:actions>
  <x-slot:right><span class="date">{{ $vehicles->total() }} {{ __('مركبة') }}</span>
    <span class="o-views"><a href="{{ $qs(['view' => 'cards']) }}" class="{{ $view === 'cards' ? 'on' : '' }}" title="{{ __('بطاقات') }}"><x-icon name="grid"/></a><a href="{{ $qs(['view' => 'list']) }}" class="{{ $view === 'list' ? 'on' : '' }}" title="{{ __('قائمة') }}"><x-icon name="list"/></a></span></x-slot:right>
  <x-slot:search>
    <form method="GET" action="{{ route('vehicles.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;flex:1;align-items:center"><input type="hidden" name="view" value="{{ $view }}">
      <label class="o-search"><x-icon name="search"/><input type="search" name="q" value="{{ $q }}" placeholder="{{ __('بحث باللوحة أو الماركة أو الموديل أو الهيكل…') }}"></label>
      <span class="o-fsel">
        <select name="status" onchange="this.form.submit()" aria-label="{{ __('الحالة') }}"><option value="current" @selected($status === 'current')>{{ __('الحالية (دون المباعة)') }}</option>@foreach(\App\Models\Vehicle::STATUSES as $s)<option value="{{ $s }}" @selected($status === $s)>{{ __('types.vehicle_status.'.$s) }}</option>@endforeach<option value="all" @selected($status === 'all')>{{ __('الكل') }}</option></select>
        <label class="pill {{ $onlyAlerts ? 'amb' : '' }}" style="cursor:pointer;height:38px"><input type="checkbox" name="alerts" value="1" @checked($onlyAlerts) onchange="this.form.submit()"> {{ __('وثائق تحتاج تجديدًا') }}</label>
      </span>
    </form>
  </x-slot:search>
</x-cp>
@endsection
@section('content')
@php $can = auth()->user()->canManageData(); $cc = \App\Support\CompanyContext::current(); @endphp
@if($vehicles->isEmpty())
  <div class="card"><div class="empty">{{ __('لا توجد مركبات مطابقة.') }}@if($can && \App\Models\Company::count())<br><br><a class="btn" href="{{ route('vehicles.create') }}"><x-icon name="plus"/> {{ __('أضف مركبة') }}</a>@endif</div></div>
@elseif($view === 'cards')
  <div class="o-kanban">
    @foreach($vehicles as $v)
      @php $warn = $v->documents->filter(fn ($d) => $d->tone() !== '')->sortBy('expiry_date')->take(2); @endphp
      <a class="o-kcard s-{{ $v->status }}" href="{{ route('vehicles.show', $v) }}">
        <span class="kpi" style="flex:none"><span class="ic blu"><x-icon name="car"/></span></span>
        <div class="ttl"><span class="plate">{{ $v->plate }}</span><b style="margin-top:6px">{{ trim(($v->make ?? '').' '.($v->model ?? '').' '.($v->year ?? '')) ?: '—' }}</b>
          <div class="meta">
            @unless($cc)<span class="pill pri">{{ $v->company?->displayName() }}</span>@endunless
            <span class="pill {{ $v->status === 'active' ? 'grn' : ($v->status === 'maintenance' ? 'amb' : ($v->status === 'out_of_service' ? 'red' : '')) }}">{{ __('types.vehicle_status.'.$v->status) }}</span>
            @if($v->driver)<span class="tag"><x-icon name="user"/> {{ $v->driver->displayName() }}</span>@endif
            @foreach($warn as $d)<span class="pill {{ $d->tone() }}">{{ $d->label() }}: {{ $d->daysLeft() < 0 ? __('منتهية') : __('بعد :n يوم', ['n' => $d->daysLeft()]) }}</span>@endforeach
          </div></div>
      </a>
    @endforeach
  </div>
@else
  <div class="o-list tbl-wrap"><table>
    <thead><tr><th>{{ __('اللوحة') }}</th>@unless($cc)<th>{{ __('الشركة') }}</th>@endunless<th>{{ __('الماركة / الموديل') }}</th><th>{{ __('السنة') }}</th><th>{{ __('السائق') }}</th><th>{{ __('العداد (كم)') }}</th><th>{{ __('الوثائق') }}</th><th>{{ __('الحالة') }}</th></tr></thead><tbody>
    @foreach($vehicles as $v)
      @php $worst = $v->documents->filter(fn ($d) => $d->tone() !== '')->sortBy('expiry_date')->first(); @endphp
      <tr class="link" onclick="location='{{ route('vehicles.show', $v) }}'">
        <td><span class="plate">{{ $v->plate }}</span></td>@unless($cc)<td>{{ $v->company?->displayName() }}</td>@endunless
        <td>{{ trim(($v->make ?? '').' '.($v->model ?? '')) ?: '—' }}</td><td>{{ $v->year ?: '—' }}</td><td>{{ $v->driver?->displayName() ?: '—' }}</td><td>{{ $v->odometer !== null ? number_format($v->odometer) : '—' }}</td>
        <td>@if($worst)<span class="pill {{ $worst->tone() }}">{{ $worst->label() }} · {{ $worst->daysLeft() < 0 ? __('منتهية') : __('بعد :n يوم', ['n' => $worst->daysLeft()]) }}</span>@else<span class="pill grn">{{ __('سليمة') }}</span>@endif</td>
        <td><span class="pill {{ $v->status === 'active' ? 'grn' : ($v->status === 'maintenance' ? 'amb' : ($v->status === 'out_of_service' ? 'red' : '')) }}">{{ __('types.vehicle_status.'.$v->status) }}</span></td>
      </tr>
    @endforeach
    </tbody></table></div>
@endif
<div style="margin-top:14px">{{ $vehicles->links() }}</div>
@endsection

@extends('layouts.odoo')
@section('title', __('الشركات').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('الشركات'), null]]">
  <x-slot:actions>@if(auth()->user()->can('companies.edit'))<a class="btn" href="{{ route('companies.create') }}"><x-icon name="plus"/> {{ __('جديد') }}</a>@endif</x-slot:actions>
</x-cp>
@endsection
@section('content')
<div class="o-kanban">
  @forelse($companies as $c)
    <a class="o-kcard s-{{ $c->is_active ? 'active' : 'inactive' }}" href="{{ route('companies.show', $c) }}">
      <span class="kpi" style="flex:none"><span class="ic vio"><x-icon name="building"/></span></span>
      <div class="ttl"><b>{{ $c->displayName() }}</b><small>{{ $c->cr_number ? __('سجل تجاري').': '.$c->cr_number : '—' }}</small>
        <div class="meta"><span class="pill pri"><x-icon name="users"/> {{ $c->employees_count }} {{ __('موظف') }}</span><span class="pill blu"><x-icon name="car"/> {{ $c->vehicles_count }} {{ __('مركبة') }}</span>@unless($c->is_active)<span class="pill">{{ __('معطّلة') }}</span>@endunless</div></div>
    </a>
  @empty
    <div class="card" style="grid-column:1/-1"><div class="empty">{{ __('لا شركات بعد.') }}@if(auth()->user()->can('companies.edit'))<br><br><a class="btn" href="{{ route('companies.create') }}"><x-icon name="plus"/> {{ __('أضف شركتك الأولى') }}</a>@endif</div></div>
  @endforelse
</div>
@endsection

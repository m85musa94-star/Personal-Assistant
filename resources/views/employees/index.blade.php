@extends('layouts.odoo')
@section('title', __('الموظفون').' — '.__('مركز القيادة'))
@section('controlpanel')
@php
    $can = auth()->user()->canManageData();
    $cc = \App\Support\CompanyContext::current();
    $qs = fn (array $o) => route('employees.index', array_filter(array_merge(['q' => $q, 'status' => $status, 'view' => $view, 'alerts' => $onlyAlerts ? 1 : null], $o), fn ($v) => $v !== null && $v !== ''));
@endphp
<x-cp :crumbs="[[__('الموظفون'), null]]">
  <x-slot:actions>@if($can)<a class="btn" href="{{ route('employees.create') }}"><x-icon name="plus"/> {{ __('جديد') }}</a>@endif</x-slot:actions>
  <x-slot:right>
    <span class="date">{{ $employees->total() }} {{ __('موظف') }}</span>
    <span class="o-views"><a href="{{ $qs(['view' => 'cards']) }}" class="{{ $view === 'cards' ? 'on' : '' }}" title="{{ __('بطاقات') }}"><x-icon name="grid"/></a><a href="{{ $qs(['view' => 'list']) }}" class="{{ $view === 'list' ? 'on' : '' }}" title="{{ __('قائمة') }}"><x-icon name="list"/></a></span>
  </x-slot:right>
  <x-slot:search>
    <form method="GET" action="{{ route('employees.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;flex:1;align-items:center">
      <input type="hidden" name="view" value="{{ $view }}">
      <label class="o-search"><x-icon name="search"/><input type="search" name="q" value="{{ $q }}" placeholder="{{ __('بحث بالاسم أو الرقم أو الجوال أو الجنسية…') }}"></label>
      <span class="o-fsel">
        <select name="status" onchange="this.form.submit()" aria-label="{{ __('الحالة') }}"><option value="active" @selected($status === 'active')>{{ __('على رأس العمل') }}</option><option value="inactive" @selected($status === 'inactive')>{{ __('غير نشط') }}</option><option value="all" @selected($status === 'all')>{{ __('الكل') }}</option></select>
        <label class="pill {{ $onlyAlerts ? 'amb' : '' }}" style="cursor:pointer;height:38px"><input type="checkbox" name="alerts" value="1" @checked($onlyAlerts) onchange="this.form.submit()"> {{ __('وثائق تحتاج تجديدًا') }}</label>
      </span>
    </form>
  </x-slot:search>
</x-cp>
@endsection
@section('content')
@if($employees->isEmpty())
  <div class="card"><div class="empty">
    {{ __('لا يوجد موظفون مطابقون.') }}
    @if($can && \App\Models\Company::count())<br><br><a class="btn" href="{{ route('employees.create') }}"><x-icon name="plus"/> {{ __('أضف موظفًا') }}</a>@endif
    @if(! \App\Models\Company::count())<br><br><a class="btn" href="{{ route('companies.create') }}">{{ __('أضف شركة أولًا') }}</a>@endif
  </div></div>
@elseif($view === 'cards')
  <div class="o-kanban">
    @foreach($employees as $e)
      @php $warn = $e->documents->filter(fn ($d) => $d->tone() !== '')->sortBy('expiry_date')->take(2); @endphp
      <a class="o-kcard s-{{ $e->status }}" href="{{ route('employees.show', $e) }}">
        <x-avatar :employee="$e" size="lg"/>
        <div class="ttl"><b>{{ $e->displayName() }}</b><small>{{ $e->job_title ?: '—' }}</small>
          <div class="meta">
            @unless($cc)<span class="pill pri">{{ $e->company?->displayName() }}</span>@endunless
            @if($e->nationality)<span class="tag">{{ $e->nationality }}</span>@endif
            @if(isset($onLeave[$e->id]))<span class="pill vio">{{ __('في إجازة') }}</span>@endif
            @unless($e->isActive())<span class="pill">{{ __('غير نشط') }}</span>@endunless
            @foreach($warn as $d)<span class="pill {{ $d->tone() }}">{{ $d->label() }}: {{ $d->daysLeft() < 0 ? __('منتهية') : __('بعد :n يوم', ['n' => $d->daysLeft()]) }}</span>@endforeach
          </div></div>
      </a>
    @endforeach
  </div>
@else
  <div class="o-list tbl-wrap"><table>
    <thead><tr><th></th><th>{{ __('الاسم') }}</th>@unless($cc)<th>{{ __('الشركة') }}</th>@endunless<th>{{ __('رقم الموظف') }}</th><th>{{ __('المسمى الوظيفي') }}</th><th>{{ __('الجنسية') }}</th><th>{{ __('الجوال') }}</th><th>{{ __('تاريخ التعيين') }}</th><th>{{ __('الوثائق') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
    <tbody>
    @foreach($employees as $e)
      @php $bad = $e->documents->filter(fn ($d) => $d->tone() !== ''); $worst = $bad->sortBy('expiry_date')->first(); @endphp
      <tr class="link" onclick="location='{{ route('employees.show', $e) }}'">
        <td><x-avatar :employee="$e" size="sm"/></td>
        <td><a href="{{ route('employees.show', $e) }}"><b>{{ $e->displayName() }}</b></a></td>
        @unless($cc)<td>{{ $e->company?->displayName() }}</td>@endunless
        <td>{{ $e->code ?: '—' }}</td><td>{{ $e->job_title ?: '—' }}</td><td>{{ $e->nationality ?: '—' }}</td><td dir="ltr" style="text-align:start">{{ $e->phone ?: '—' }}</td>
        <td>{{ $e->hire_date?->fmt() ?: '—' }}</td>
        <td>@if($worst)<span class="pill {{ $worst->tone() }}">{{ $worst->label() }} · {{ $worst->daysLeft() < 0 ? __('منتهية') : __('بعد :n يوم', ['n' => $worst->daysLeft()]) }}</span>@else<span class="pill grn">{{ __('سليمة') }}</span>@endif</td>
        <td>@if(isset($onLeave[$e->id]))<span class="pill vio">{{ __('في إجازة') }}</span>@else<span class="pill {{ $e->isActive() ? 'grn' : '' }}">{{ __('types.employee_status.'.$e->status) }}</span>@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
@endif
<div style="margin-top:14px">{{ $employees->links() }}</div>
@endsection

@extends('layouts.shell')
@section('title', __('الموظفون').' — '.__('مركز القيادة'))
@section('content')
@php $qs = fn (array $o) => route('hr.employees.index', array_filter(array_merge(['q' => $q, 'status' => $status, 'department' => $dept, 'view' => $view], $o))); @endphp
<div class="page-h"><h1>{{ __('الموظفون') }}</h1><span class="sp"></span>
  <a class="ibtn" href="{{ $qs(['view' => 'cards']) }}" title="{{ __('بطاقات') }}" style="{{ $view === 'cards' ? 'border-color:var(--pri);color:var(--pri)' : '' }}"><x-icon name="grid"/></a>
  <a class="ibtn" href="{{ $qs(['view' => 'list']) }}" title="{{ __('قائمة') }}" style="{{ $view === 'list' ? 'border-color:var(--pri);color:var(--pri)' : '' }}"><x-icon name="list"/></a>
  @if(auth()->user()->isHrManager())<a class="btn" href="{{ route('hr.employees.create') }}"><x-icon name="plus"/> {{ __('موظف جديد') }}</a>@endif
</div>
<form class="filters" method="GET" action="{{ route('hr.employees.index') }}">
  <input type="hidden" name="view" value="{{ $view }}">
  <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('بحث بالاسم أو الرقم أو المسمى…') }}" style="flex:1;min-width:200px">
  <select name="department" onchange="this.form.submit()"><option value="">{{ __('كل الأقسام') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((string) $dept === (string) $d->id)>{{ $d->displayName() }}</option>@endforeach</select>
  <select name="status" onchange="this.form.submit()"><option value="active" @selected($status === 'active')>{{ __('نشط') }}</option><option value="inactive" @selected($status === 'inactive')>{{ __('غير نشط') }}</option><option value="all" @selected($status === 'all')>{{ __('الكل') }}</option></select>
  <button class="btn sec">{{ __('بحث') }}</button>
</form>

@if($employees->isEmpty())
  <div class="card"><div class="empty">{{ __('لا يوجد موظفون مطابقون.') }}</div></div>
@elseif($view === 'cards')
  <div class="grid g3">
    @foreach($employees as $e)
      <a class="card ecard" href="{{ route('hr.employees.show', $e) }}" style="color:inherit;text-decoration:none">
        <x-avatar :employee="$e" size="lg"/>
        <div class="ttl"><b>{{ $e->displayName() }}</b><small>{{ $e->job_title ?: '—' }}</small>
          <div class="meta">@if($e->department)<span class="pill pri">{{ $e->department->displayName() }}</span>@endif
            @if(! $e->isActive())<span class="pill red">{{ __('غير نشط') }}</span>@endif</div></div>
      </a>
    @endforeach
  </div>
@else
  <div class="card tbl-wrap"><table>
    <tr><th></th><th>{{ __('الاسم') }}</th><th>{{ __('رقم الموظف') }}</th><th>{{ __('المسمى الوظيفي') }}</th><th>{{ __('القسم') }}</th><th>{{ __('المدير المباشر') }}</th><th>{{ __('تاريخ التعيين') }}</th><th>{{ __('الحالة') }}</th></tr>
    @foreach($employees as $e)
      <tr><td><x-avatar :employee="$e" size="sm"/></td>
        <td><a href="{{ route('hr.employees.show', $e) }}"><b>{{ $e->displayName() }}</b></a></td>
        <td>{{ $e->code ?: '—' }}</td><td>{{ $e->job_title ?: '—' }}</td>
        <td>{{ $e->department?->displayName() ?: '—' }}</td><td>{{ $e->manager?->displayName() ?: '—' }}</td>
        <td>{{ $e->hire_date?->fmt() ?: '—' }}</td>
        <td><span class="pill {{ $e->isActive() ? 'grn' : 'red' }}">{{ $e->isActive() ? __('نشط') : __('غير نشط') }}</span></td></tr>
    @endforeach
  </table></div>
@endif
<div style="margin-top:14px">{{ $employees->links() }}</div>
@endsection

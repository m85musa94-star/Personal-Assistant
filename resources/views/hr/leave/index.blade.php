@extends('layouts.shell')
@section('title', __('الإجازات').' — '.__('مركز القيادة'))
@section('content')
@php $n = fn ($x) => rtrim(rtrim(number_format($x, 1), '0'), '.'); @endphp
<div class="page-h"><h1>{{ __('الإجازات') }}</h1><span class="sp"></span>
  @if($isHr)
    <a class="ibtn" href="{{ route('hr.leave.index', ['tab' => $tab, 'layout' => 'table']) }}" title="{{ __('جدول') }}" style="{{ $layout === 'table' ? 'border-color:var(--pri);color:var(--pri)' : '' }}"><x-icon name="list"/></a>
    <a class="ibtn" href="{{ route('hr.leave.index', ['tab' => $tab, 'layout' => 'board']) }}" title="{{ __('لوحة') }}" style="{{ $layout === 'board' ? 'border-color:var(--pri);color:var(--pri)' : '' }}"><x-icon name="board"/></a>
  @endif
  <a class="btn" href="{{ route('hr.leave.create') }}"><x-icon name="plus"/> {{ __('طلب إجازة') }}</a>
</div>
@if($balances->isNotEmpty())
<div class="grid g4" style="margin-bottom:16px">
  @foreach($balances as $b)
    <div class="card kpi"><span class="ic vio"><x-icon name="calendar-off"/></span><div><b>@if($b['left'] === null)—@else{{ $n($b['left']) }}@endif</b><span>{{ $b['type']->displayName() }}@if($b['left'] === null) · {{ __('الاستحقاق غير محدد') }}@endif</span></div></div>
  @endforeach
</div>
@endif
@if(count($tabs) > 1)<div class="tabs">@foreach($tabs as $k => $label)<a href="{{ route('hr.leave.index', ['tab' => $k, 'layout' => $layout]) }}" class="{{ $tab === $k ? 'on' : '' }}">{{ $label }}</a>@endforeach</div>@endif

@php
  $actions = function ($l) { return null; };
@endphp
@if($layout === 'board')
  <div class="board" style="grid-template-columns:repeat(3,minmax(260px,1fr))">
    @foreach(['pending' => 'قيد الانتظار', 'approved' => 'معتمدة', 'rejected' => 'مرفوضة'] as $st => $label)
      <div class="col {{ $st }}"><h3>{{ __($label) }}<span class="tag">{{ $requests->where('status', $st)->count() }}</span></h3>
        @foreach($requests->where('status', $st) as $l)
          <div class="kc"><b>{{ $l->employee->displayName() }}</b>
            <div class="meta"><span class="tag">{{ $l->type->displayName() }}</span><span class="tag">{{ $l->start_date->fmt() }} → {{ $l->end_date->fmt() }}</span><span class="tag">{{ $n($l->days) }} {{ __('يوم') }}</span></div></div>
        @endforeach
      </div>
    @endforeach
  </div>
@else
<div class="card tbl-wrap">
  @if($requests->isEmpty())<div class="empty">{{ __('لا طلبات') }}</div>@else
  <table>
    <tr><th>{{ __('الموظف') }}</th><th>{{ __('النوع') }}</th><th>{{ __('الفترة') }}</th><th>{{ __('الأيام') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('إجراءات') }}</th></tr>
    @foreach($requests as $l)
      @php
        $user = auth()->user(); $own = $user->employee && $l->employee_id === $user->employee->id;
        $canDecide = $l->status === 'pending' && $user->canDecideLeaveFor($l->employee) && (! $own || $user->is_admin);
        $canCancel = ($own || $isHr) && ($l->status === 'pending' || ($l->status === 'approved' && $l->start_date->gte(today())));
      @endphp
      <tr>
        <td><span style="display:inline-flex;gap:8px;align-items:center"><x-avatar :employee="$l->employee" size="sm"/><a href="{{ route('hr.employees.show', $l->employee) }}">{{ $l->employee->displayName() }}</a></span></td>
        <td>{{ $l->type->displayName() }}</td>
        <td>{{ $l->start_date->fmt() }} → {{ $l->end_date->fmt() }}@if($l->reason)<br><small class="date">{{ $l->reason }}</small>@endif</td>
        <td>{{ $n($l->days) }}</td>
        <td><x-leave-status :status="$l->status"/>@if($l->decision_note)<br><small class="date">{{ $l->decision_note }}</small>@endif</td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
          @if($canDecide)
            <form method="POST" action="{{ route('hr.leave.approve', $l) }}">@csrf<button class="btn sm grn">{{ __('اعتماد') }}</button></form>
            <form method="POST" action="{{ route('hr.leave.reject', $l) }}" style="display:flex;gap:4px">@csrf<input name="decision_note" placeholder="{{ __('سبب الرفض') }}" required style="width:130px"><button class="btn sm red">{{ __('رفض') }}</button></form>
          @endif
          @if($canCancel)<form method="POST" action="{{ route('hr.leave.cancel', $l) }}" onsubmit="return confirm('{{ __('إلغاء هذا الطلب؟') }}')">@csrf<button class="btn sm sec">{{ __('إلغاء') }}</button></form>@endif
          </div>
        </td>
      </tr>
    @endforeach
  </table>@endif
</div>
@endif
@endsection

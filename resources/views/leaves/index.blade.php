@extends('layouts.odoo')
@section('title', __('الإجازات').' — '.__('مركز القيادة'))
@section('controlpanel')
@php $can = auth()->user()->can('leaves.edit'); $cc = \App\Support\CompanyContext::current(); @endphp
<x-cp :crumbs="[[__('الإجازات'), null]]">
  <x-slot:actions>@if($can)<a class="btn" href="{{ route('leaves.create') }}"><x-icon name="plus"/> {{ __('تسجيل إجازة') }}</a>@endif</x-slot:actions>
  <x-slot:right><span class="date">{{ $leaves->total() }} {{ __('سجل') }}</span></x-slot:right>
  <x-slot:search>
    <form method="GET" action="{{ route('leaves.index') }}" class="o-fsel" style="flex:1">
      <select name="employee" onchange="this.form.submit()" aria-label="{{ __('الموظف') }}"><option value="">{{ __('كل الموظفين') }}</option>@foreach($employees as $e)<option value="{{ $e->id }}" @selected((string) $employeeId === (string) $e->id)>{{ $e->displayName() }}</option>@endforeach</select>
      <select name="type" onchange="this.form.submit()" aria-label="{{ __('نوع الإجازة') }}"><option value="">{{ __('كل الأنواع') }}</option>@foreach($types as $t)<option value="{{ $t->id }}" @selected((string) $typeId === (string) $t->id)>{{ $t->displayName() }}</option>@endforeach</select>
      <select name="status" onchange="this.form.submit()" aria-label="{{ __('الحالة') }}"><option value="approved" @selected($status === 'approved')>{{ __('types.leave_status.approved') }}</option><option value="cancelled" @selected($status === 'cancelled')>{{ __('types.leave_status.cancelled') }}</option><option value="all" @selected($status === 'all')>{{ __('الكل') }}</option></select>
      <input type="number" name="year" value="{{ $year }}" min="2000" max="2100" placeholder="{{ __('السنة') }}" style="width:100px;border-radius:999px;height:38px" onchange="this.form.submit()" aria-label="{{ __('السنة') }}">
    </form>
  </x-slot:search>
</x-cp>
@endsection
@section('content')
@php $n = fn ($x) => rtrim(rtrim(number_format($x, 1), '0'), '.'); @endphp
@if($onLeave->isNotEmpty())
  <div class="card" style="margin-bottom:14px"><h3><x-icon name="calendar-off"/> {{ __('في إجازة اليوم') }}</h3>
    <div class="meta">@foreach($onLeave as $l)<a class="pill vio" href="{{ route('employees.show', ['employee' => $l->employee, 'tab' => 'leaves']) }}">{{ $l->employee->displayName() }} · {{ __('حتى') }} {{ $l->end_date->fmt() }}</a>@endforeach</div></div>
@endif
<div class="o-list tbl-wrap">
  @if($leaves->isEmpty())<div class="empty">{{ __('لا إجازات مسجّلة.') }}</div>@else
  <table><thead><tr><th>{{ __('الموظف') }}</th>@unless($cc)<th>{{ __('الشركة') }}</th>@endunless<th>{{ __('النوع') }}</th><th>{{ __('الفترة') }}</th><th>{{ __('الأيام') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('ملاحظات') }}</th><th></th></tr></thead><tbody>
    @foreach($leaves as $l)
      <tr class="{{ $l->status === 'cancelled' ? 'done' : '' }}">
        <td><span style="display:inline-flex;gap:8px;align-items:center"><x-avatar :employee="$l->employee" size="sm"/><a href="{{ route('employees.show', ['employee' => $l->employee, 'tab' => 'leaves']) }}">{{ $l->employee->displayName() }}</a></span></td>
        @unless($cc)<td>{{ $l->employee->company?->displayName() }}</td>@endunless
        <td>{{ $l->type->displayName() }}</td><td>{{ $l->start_date->fmt() }} → {{ $l->end_date->fmt() }}</td><td>{{ $n($l->days) }}</td>
        <td><span class="pill {{ $l->status === 'approved' ? 'grn' : '' }}">{{ __('types.leave_status.'.$l->status) }}</span></td><td>{{ $l->reason }}</td>
        <td>@if($can)<form method="POST" action="{{ route('leaves.toggle', $l) }}">@csrf<button class="btn sm sec">{{ $l->status === 'approved' ? __('إلغاء') : __('استعادة') }}</button></form>@endif</td>
      </tr>
    @endforeach
  </tbody></table>@endif
</div>
<div style="margin-top:14px">{{ $leaves->links() }}</div>
@endsection

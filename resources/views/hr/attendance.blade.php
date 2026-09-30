@extends('layouts.shell')
@section('title', __('الحضور والانصراف').' — '.__('مركز القيادة'))
@section('content')
<div class="page-h"><h1>{{ __('الحضور والانصراف') }}</h1></div>
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="grid g2">
  <div class="card">
    <h3><x-icon name="clock"/> {{ __('تسجيلك اليوم') }}</h3>
    @if(! $me)
      <div class="empty">{{ __('حسابك غير مرتبط بموظف. اطلب من مسؤول الموارد البشرية ربطه.') }}</div>
    @elseif($open)
      <p>{{ __('على رأس العمل منذ') }} <b>{{ $open->check_in->format('H:i') }}</b> ({{ $open->check_in->fmt('D j M') }}) — <span class="pill {{ $open->hours() > 16 ? 'red' : 'grn' }}">{{ $open->hours() }} {{ __('س') }}</span></p>
      @if($open->hours() > 16)<div class="warn">{{ __('تسجيل الحضور مفتوح منذ أكثر من 16 ساعة؛ غالبًا نُسي الانصراف. سجّله وأخبر الموارد البشرية لتصحيحه.') }}</div>@endif
      <form method="POST" action="{{ route('hr.attendance.out') }}">@csrf<button class="btn red"><x-icon name="stop"/> {{ __('تسجيل انصراف') }}</button></form>
    @else
      <p class="sub">{{ __('لم تسجّل حضورك بعد.') }}</p>
      <form method="POST" action="{{ route('hr.attendance.in') }}">@csrf<button class="btn"><x-icon name="play"/> {{ __('تسجيل حضور') }}</button></form>
    @endif
  </div>
  <div class="card">
    <h3>{{ __('ملخص الشهر') }} — {{ $monthLabel }}</h3>
    @forelse($totals as $t)
      <div class="row static"><x-avatar :employee="$t['employee']" size="sm"/><div class="ttl">{{ $t['employee']->displayName() }}</div><span class="tag">{{ $t['days'] }} {{ __('يوم') }}</span><span class="pill pri">{{ $t['hours'] }} {{ __('س') }}</span></div>
    @empty <div class="empty">{{ __('لا سجلات هذا الشهر') }}</div> @endforelse
  </div>
</div>
<div class="card" style="margin-top:16px">
  <div class="top" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
    <a class="btn sec sm" href="{{ route('hr.attendance.index', ['month' => $prev, 'employee' => $employeeId ?: null]) }}">{{ app()->getLocale() === 'ar' ? '→' : '←' }}</a>
    <b>{{ $monthLabel }}</b>
    <a class="btn sec sm" href="{{ route('hr.attendance.index', ['month' => $next, 'employee' => $employeeId ?: null]) }}">{{ app()->getLocale() === 'ar' ? '←' : '→' }}</a>
    @if($visible->count() > 1)
      <form method="GET" style="margin-inline-start:auto"><input type="hidden" name="month" value="{{ $month }}">
        <select name="employee" onchange="this.form.submit()"><option value="">{{ __('كل الموظفين') }}</option>@foreach($visible as $e)<option value="{{ $e->id }}" @selected($employeeId === $e->id)>{{ $e->displayName() }}</option>@endforeach</select></form>
    @endif
  </div>
  <div class="tbl-wrap">
  @if($records->isEmpty())<div class="empty">{{ __('لا سجلات') }}</div>@else
  <table><tr><th>{{ __('الموظف') }}</th><th>{{ __('التاريخ') }}</th><th>{{ __('حضور') }}</th><th>{{ __('انصراف') }}</th><th>{{ __('المدة') }}</th><th>{{ __('المصدر') }}</th>@if($isHr)<th></th>@endif</tr>
    @foreach($records as $r)
      <tr><td>{{ $r->employee->displayName() }}</td><td>{{ $r->check_in->fmt('D j M') }}</td><td>{{ $r->check_in->format('H:i') }}</td>
        <td>@if($r->check_out){{ $r->check_out->format('H:i') }}@else<span class="pill amb">{{ __('مفتوح') }}</span>@endif</td>
        <td>{{ $r->hours() }} {{ __('س') }}</td><td><span class="tag">{{ $r->source === 'manual' ? __('يدوي') : __('الويب') }}</span>@if($r->note) <small class="date">{{ $r->note }}</small>@endif</td>
        @if($isHr)<td><form method="POST" action="{{ route('hr.attendance.destroy', $r) }}" onsubmit="return confirm('{{ __('حذف السجل؟') }}')">@csrf @method('DELETE')<button class="btn sm sec"><x-icon name="x"/></button></form></td>@endif</tr>
    @endforeach
  </table>@endif
  </div>
</div>
@if($isHr)
<div class="card" style="margin-top:16px"><h3>{{ __('إضافة سجل يدوي') }}</h3>
  <form method="POST" action="{{ route('hr.attendance.store') }}" class="f">@csrf
    <label>{{ __('الموظف') }}<select name="employee_id" required>@foreach($visible as $e)<option value="{{ $e->id }}">{{ $e->displayName() }}</option>@endforeach</select></label>
    <label>{{ __('ملاحظة') }}<input name="note" maxlength="200"></label>
    <label>{{ __('حضور') }}<input type="datetime-local" name="check_in" required></label>
    <label>{{ __('انصراف') }}<input type="datetime-local" name="check_out"></label>
    <button class="btn w">{{ __('إضافة') }}</button></form></div>
@endif
@endsection

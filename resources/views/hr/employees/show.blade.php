@extends('layouts.shell')
@section('title', $employee->displayName().' — '.__('مركز القيادة'))
@section('content')
@php $isHr = auth()->user()->isHrManager(); @endphp
<div class="page-h"><a class="ibtn" href="{{ route('hr.employees.index') }}" title="{{ __('الموظفون') }}">{{ app()->getLocale() === 'ar' ? '→' : '←' }}</a>
  <h1>{{ $employee->displayName() }}</h1><span class="pill {{ $employee->isActive() ? 'grn' : 'red' }}">{{ $employee->isActive() ? __('نشط') : __('غير نشط') }}</span><span class="sp"></span>
  @if($isHr)<a class="btn" href="{{ route('hr.employees.edit', $employee) }}"><x-icon name="edit"/> {{ __('تعديل') }}</a>@endif
</div>
<div class="grid g2">
  <div class="card">
    <div class="ecard"><x-avatar :employee="$employee" size="lg"/>
      <div class="ttl"><b style="font-size:18px">{{ $employee->displayName() }}</b><small>{{ $employee->job_title ?: '—' }}</small>
        <div class="meta">@if($employee->department)<span class="pill pri">{{ $employee->department->displayName() }}</span>@endif @if($employee->code)<span class="tag">#{{ $employee->code }}</span>@endif</div></div></div>
    <table style="margin-top:14px">
      <tr><th>{{ __('البريد الإلكتروني') }}</th><td dir="ltr" style="text-align:start">{{ $employee->email ?: '—' }}</td></tr>
      <tr><th>{{ __('الجوال') }}</th><td dir="ltr" style="text-align:start">{{ $employee->phone ?: '—' }}</td></tr>
      <tr><th>{{ __('المدير المباشر') }}</th><td>@if($employee->manager)<a href="{{ route('hr.employees.show', $employee->manager) }}">{{ $employee->manager->displayName() }}</a>@else — @endif</td></tr>
      <tr><th>{{ __('تاريخ التعيين') }}</th><td>{{ $employee->hire_date?->fmt() ?: '—' }}</td></tr>
      <tr><th>{{ __('حساب الدخول') }}</th><td dir="ltr" style="text-align:start">{{ $employee->user?->email ?: '—' }}</td></tr>
      @if($employee->reports->isNotEmpty())<tr><th>{{ __('المرؤوسون') }}</th><td>@foreach($employee->reports as $r)<a class="tag" href="{{ route('hr.employees.show', $r) }}">{{ $r->displayName() }}</a> @endforeach</td></tr>@endif
    </table>
  </div>

  @if($sensitive)
  <div class="card">
    <h3><x-icon name="idcard"/> {{ __('الوثائق والعقد') }}</h3>
    @php $rel = fn ($d) => $d ? ($d->isPast() && ! $d->isToday() ? 'red' : (today()->diffInDays($d, false) <= 60 ? 'amb' : '')) : ''; @endphp
    <table>
      <tr><th>{{ __('الهوية / الإقامة') }}</th><td>{{ $employee->id_number ?: '—' }} @if($employee->id_expiry)<span class="pill {{ $rel($employee->id_expiry) }}">{{ $employee->id_expiry->fmt() }}</span>@endif</td></tr>
      <tr><th>{{ __('جواز السفر') }}</th><td>{{ $employee->passport_number ?: '—' }} @if($employee->passport_expiry)<span class="pill {{ $rel($employee->passport_expiry) }}">{{ $employee->passport_expiry->fmt() }}</span>@endif</td></tr>
      <tr><th>{{ __('نوع العقد') }}</th><td>{{ $employee->contract_type ? __('contract.'.$employee->contract_type) : '—' }}</td></tr>
      <tr><th>{{ __('مدة العقد') }}</th><td>{{ $employee->contract_start?->fmt() ?: '—' }} → {{ $employee->contract_end?->fmt() ?: '—' }} @if($employee->contract_end)<span class="pill {{ $rel($employee->contract_end) }}">{{ __('ينتهي') }}</span>@endif</td></tr>
      @if($isHr)<tr><th>{{ __('الراتب') }}</th><td>{{ $employee->salary !== null ? number_format((float) $employee->salary, 2) : '—' }}</td></tr>@endif
    </table>
    @if($employee->notes)<p class="sub" style="margin-top:10px">{{ $employee->notes }}</p>@endif
  </div>
  @endif

  <div class="card">
    <h3><x-icon name="calendar-off"/> {{ __('أرصدة الإجازات') }} ({{ today()->format('Y') }})</h3>
    @foreach($balances as $b)
      <div class="row static"><div class="ttl">{{ $b['type']->displayName() }}</div>
        @if($b['left'] === null)<span class="pill">{{ __('الاستحقاق غير محدد') }}</span>@else<span class="pill {{ $b['left'] < 0 ? 'red' : 'grn' }}">{{ rtrim(rtrim(number_format($b['left'], 1), '0'), '.') }} / {{ rtrim(rtrim(number_format($b['type']->annual_days, 1), '0'), '.') }} {{ __('يوم') }}</span>@endif</div>
    @endforeach
    <h3 style="margin-top:14px">{{ __('آخر الطلبات') }}</h3>
    @forelse($leaves as $l)
      <div class="row static"><div class="ttl">{{ $l->type->displayName() }}<div class="meta"><span class="tag">{{ $l->start_date->fmt() }} → {{ $l->end_date->fmt() }}</span><span class="tag">{{ rtrim(rtrim(number_format($l->days, 1), '0'), '.') }} {{ __('يوم') }}</span></div></div><x-leave-status :status="$l->status"/></div>
    @empty <div class="empty">{{ __('لا طلبات') }}</div> @endforelse
  </div>

  <div class="card">
    <h3><x-icon name="clock"/> {{ __('آخر سجلات الحضور') }}</h3>
    @forelse($attendance as $a)
      <div class="row static"><div class="ttl">{{ $a->check_in->fmt('D j M') }}<div class="meta"><span class="tag">{{ $a->check_in->format('H:i') }} → {{ $a->check_out?->format('H:i') ?: '…' }}</span></div></div><span class="pill {{ $a->check_out ? '' : 'amb' }}">{{ $a->hours() }} {{ __('س') }}</span></div>
    @empty <div class="empty">{{ __('لا سجلات') }}</div> @endforelse
  </div>
</div>
@if(auth()->user()->is_admin)
<form method="POST" action="{{ route('hr.employees.destroy', $employee) }}" onsubmit="return confirm('{{ __('حذف الموظف نهائيًا مع كل سجلات إجازاته وحضوره؟ الأفضل تعطيله بدل الحذف.') }}')" style="margin-top:18px">@csrf @method('DELETE')
  <button class="btn sec sm">{{ __('حذف نهائي') }}</button></form>
@endif
@endsection

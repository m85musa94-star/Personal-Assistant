@extends('layouts.print')
@section('title', $employee->displayName().' — '.__('ملف الموظف'))
@section('content')
@php $n = fn ($x) => rtrim(rtrim(number_format($x, 1), '0'), '.'); @endphp
<h1>{{ __('ملف الموظف') }}: {{ $employee->displayName() }}</h1>
<div class="date">{{ $employee->company?->displayName() }} @if($employee->code)· #{{ $employee->code }}@endif · {{ __('types.employee_status.'.$employee->status) }}</div>
<h2>{{ __('البيانات الأساسية') }}</h2>
<div class="kvg">
  <div><b>{{ __('الاسم') }}:</b>{{ $employee->name }}</div><div><b>{{ __('الاسم (إنجليزي)') }}:</b>{{ $employee->name_en ?: '—' }}</div>
  <div><b>{{ __('المسمى الوظيفي') }}:</b>{{ $employee->job_title ?: '—' }}</div><div><b>{{ __('الجنسية') }}:</b>{{ \App\Support\Nationalities::label($employee->nationality) ?: '—' }}</div>
  <div><b>{{ __('تاريخ التعيين') }}:</b>{{ $employee->hire_date?->fmt() ?: '—' }}</div><div><b>{{ __('الجوال') }}:</b><span dir="ltr">{{ $employee->phone ?: '—' }}</span></div>
  <div><b>{{ __('البريد الإلكتروني') }}:</b><span dir="ltr">{{ $employee->email ?: '—' }}</span></div>
  @if($employee->vehicles->isNotEmpty())<div><b>{{ __('المركبات') }}:</b>{{ $employee->vehicles->pluck('plate')->implode('، ') }}</div>@endif
</div>
<h2>{{ __('الوثائق') }}</h2>
<table class="ptable"><tr><th>{{ __('النوع') }}</th><th>{{ __('الرقم') }}</th><th>{{ __('جهة الإصدار') }}</th><th>{{ __('الإصدار') }}</th><th>{{ __('الانتهاء') }}</th></tr>
@forelse($documents as $d)<tr><td>{{ $d->label() }}</td><td dir="ltr" style="text-align:start">{{ $d->number ?: '—' }}</td><td>{{ $d->provider ?: '—' }}</td><td>{{ $d->issue_date?->fmt() ?: '—' }}</td><td>{{ $d->expiry_date?->fmt() ?: '—' }}@if($d->tone() === 'red') ({{ __('منتهية') }})@endif</td></tr>
@empty<tr><td colspan="5">{{ __('لا وثائق مسجّلة بعد.') }}</td></tr>@endforelse</table>
@if($canLeaves)
<h2>{{ __('الإجازات') }}</h2>
<table class="ptable"><tr><th>{{ __('النوع') }}</th><th>{{ __('الفترة') }}</th><th>{{ __('الأيام') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('ملاحظات') }}</th></tr>
@forelse($leaves as $l)<tr><td>{{ $l->type->displayName() }}</td><td>{{ $l->start_date->fmt() }} → {{ $l->end_date->fmt() }}</td><td>{{ $n($l->days) }}</td><td>{{ __('types.leave_status.'.$l->status) }}</td><td>{{ $l->reason }}</td></tr>
@empty<tr><td colspan="5">{{ __('لا إجازات مسجّلة.') }}</td></tr>@endforelse</table>
@endif
<h2>{{ __('سجل الموظف') }}</h2>
<table class="ptable"><tr><th>{{ __('التاريخ') }}</th><th>{{ __('النوع') }}</th><th>{{ __('الوصف') }}</th><th>{{ __('بواسطة') }}</th></tr>
@forelse($records as $r)<tr><td>{{ $r->event_date->fmt() }}</td><td>{{ __('types.employee_record.'.$r->type) }}</td><td><b>{{ $r->text() }}</b>@if($r->body)<br>{{ $r->body }}@endif</td><td>{{ $r->user?->name ?? '—' }}</td></tr>
@empty<tr><td colspan="4">{{ __('لا إدخالات في السجل بعد.') }}</td></tr>@endforelse</table>
@endsection

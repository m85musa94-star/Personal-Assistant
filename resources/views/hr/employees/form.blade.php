@extends('layouts.shell')
@php $isNew = ! $employee->exists; $v = fn ($k, $d = null) => old($k, $employee->{$k} ?? $d); $dt = fn ($k) => old($k, $employee->{$k}?->format('Y-m-d')); @endphp
@section('title', ($isNew ? __('موظف جديد') : __('تعديل موظف')).' — '.__('مركز القيادة'))
@section('content')
<div class="page-h"><h1>{{ $isNew ? __('موظف جديد') : __('تعديل موظف') }}</h1></div>
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<form method="POST" action="{{ $isNew ? route('hr.employees.store') : route('hr.employees.update', $employee) }}">
@csrf @if(! $isNew) @method('PUT') @endif
<div class="grid g2">
  <div class="card"><h3>{{ __('البيانات الأساسية') }}</h3><div class="f">
    <label>{{ __('الاسم (عربي)') }}<input name="name" value="{{ $v('name') }}" required></label>
    <label>{{ __('الاسم (إنجليزي)') }}<input name="name_en" value="{{ $v('name_en') }}" dir="ltr"></label>
    <label>{{ __('رقم الموظف') }}<input name="code" value="{{ $v('code') }}" dir="ltr"></label>
    <label>{{ __('المسمى الوظيفي') }}<input name="job_title" value="{{ $v('job_title') }}"></label>
    <label>{{ __('البريد الإلكتروني') }}<input type="email" name="email" value="{{ $v('email') }}" dir="ltr"></label>
    <label>{{ __('الجوال') }}<input name="phone" value="{{ $v('phone') }}" dir="ltr"></label>
    <label>{{ __('القسم') }}<select name="department_id"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((string) $v('department_id') === (string) $d->id)>{{ $d->displayName() }}</option>@endforeach</select></label>
    <label>{{ __('المدير المباشر') }}<select name="manager_id"><option value="">—</option>@foreach($managers as $m)<option value="{{ $m->id }}" @selected((string) $v('manager_id') === (string) $m->id)>{{ $m->displayName() }}</option>@endforeach</select></label>
    <label>{{ __('تاريخ التعيين') }}<input type="date" name="hire_date" value="{{ $dt('hire_date') }}"></label>
    <label>{{ __('الحالة') }}<select name="status"><option value="active" @selected($v('status', 'active') === 'active')>{{ __('نشط') }}</option><option value="inactive" @selected($v('status') === 'inactive')>{{ __('غير نشط') }}</option></select></label>
    <label class="w">{{ __('حساب الدخول المرتبط (لتفعيل الحضور والإجازات الذاتية)') }}<select name="user_id"><option value="">—</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected((string) $v('user_id') === (string) $u->id)>{{ $u->name }} — {{ $u->email }}</option>@endforeach</select></label>
  </div></div>
  <div class="card"><h3>{{ __('الوثائق والعقد (بيانات حساسة)') }}</h3><div class="f">
    <label>{{ __('رقم الهوية / الإقامة') }}<input name="id_number" value="{{ $v('id_number') }}" dir="ltr"></label>
    <label>{{ __('انتهاء الهوية / الإقامة') }}<input type="date" name="id_expiry" value="{{ $dt('id_expiry') }}"></label>
    <label>{{ __('رقم جواز السفر') }}<input name="passport_number" value="{{ $v('passport_number') }}" dir="ltr"></label>
    <label>{{ __('انتهاء جواز السفر') }}<input type="date" name="passport_expiry" value="{{ $dt('passport_expiry') }}"></label>
    <label>{{ __('نوع العقد') }}<select name="contract_type"><option value="">—</option>@foreach(\App\Models\Employee::CONTRACT_TYPES as $c)<option value="{{ $c }}" @selected($v('contract_type') === $c)>{{ __('contract.'.$c) }}</option>@endforeach</select></label>
    <label>{{ __('الراتب') }}<input type="number" step="0.01" min="0" name="salary" value="{{ $v('salary') }}" dir="ltr"></label>
    <label>{{ __('بداية العقد') }}<input type="date" name="contract_start" value="{{ $dt('contract_start') }}"></label>
    <label>{{ __('نهاية العقد') }}<input type="date" name="contract_end" value="{{ $dt('contract_end') }}"></label>
    <label class="w">{{ __('ملاحظات') }}<textarea name="notes" rows="3">{{ $v('notes') }}</textarea></label>
  </div></div>
</div>
<div style="display:flex;gap:8px;margin-top:16px"><button class="btn">{{ __('حفظ') }}</button><a class="btn sec" href="{{ $isNew ? route('hr.employees.index') : route('hr.employees.show', $employee) }}">{{ __('إلغاء') }}</a></div>
</form>
@endsection

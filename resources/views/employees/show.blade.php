@extends('layouts.odoo')
@section('title', $employee->displayName().' — '.__('مركز القيادة'))
@php
    $can = auth()->user()->canManageData();
    $badDocs = $documents->filter(fn ($d) => $d->tone() !== '')->count();
    $editDoc = (int) request('edit_doc');
    $n = fn ($x) => rtrim(rtrim(number_format($x, 1), '0'), '.');
@endphp
@section('controlpanel')
<x-cp :crumbs="[[__('الموظفون'), route('employees.index')], [$employee->displayName(), null]]">
  <x-slot:actions>
    @if($can)<button form="ef" class="btn">{{ __('حفظ') }}</button><a class="btn sec" href="{{ route('leaves.create', ['employee' => $employee->id]) }}"><x-icon name="calendar-off"/> {{ __('تسجيل إجازة') }}</a>@endif
  </x-slot:actions>
</x-cp>
@endsection
@section('content')
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="o-sheet">
  <div class="o-smart">
    <a href="{{ route('employees.show', ['employee' => $employee, 'tab' => 'documents']) }}" class="{{ $badDocs ? 'warn-s' : '' }}"><x-icon name="idcard"/><span><b>{{ $documents->count() }}</b><small>{{ __('الوثائق') }}@if($badDocs) · {{ $badDocs }} ⚠@endif</small></span></a>
    <a href="{{ route('employees.show', ['employee' => $employee, 'tab' => 'leaves']) }}"><x-icon name="calendar-off"/><span><b>{{ $leaves->where('status', 'approved')->count() }}</b><small>{{ __('الإجازات') }}</small></span></a>
    @if($employee->vehicles->isNotEmpty())<a href="{{ route('vehicles.index', ['q' => $employee->vehicles->first()->plate]) }}"><x-icon name="car"/><span><b>{{ $employee->vehicles->count() }}</b><small>{{ __('المركبات') }}</small></span></a>@endif
  </div>

  <form id="ef" method="POST" action="{{ route('employees.update', $employee) }}">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="hidden" name="status" value="{{ $employee->status }}">
    <div class="o-statusbar">
      @foreach(['active', 'inactive'] as $st)
        @if($can && $employee->status !== $st)<button form="ef" name="status" value="{{ $st }}">{{ __('types.employee_status.'.$st) }}</button>
        @else<span class="{{ $employee->status === $st ? 'on' : '' }}">{{ __('types.employee_status.'.$st) }}</span>@endif
      @endforeach
      @if($onLeave)<span class="on" style="background:var(--vio);border-color:var(--vio);margin-inline-start:10px">{{ __('في إجازة اليوم') }}</span>@endif
    </div>
    @include('employees._fields')
    @if($tab === 'notes')
      <div class="o-f full" style="margin-top:14px"><label>{{ __('ملاحظات') }}</label><textarea name="notes" rows="6" @disabled(! $can)>{{ old('notes', $employee->notes) }}</textarea></div>
    @endif
  </form>

  <div class="o-notebook">
    <div class="o-tabs">
      <a href="{{ route('employees.show', ['employee' => $employee, 'tab' => 'documents']) }}" class="{{ $tab === 'documents' ? 'on' : '' }}">{{ __('الوثائق (الإقامة والتأمين…)') }}</a>
      <a href="{{ route('employees.show', ['employee' => $employee, 'tab' => 'leaves']) }}" class="{{ $tab === 'leaves' ? 'on' : '' }}">{{ __('الإجازات') }}</a>
      <a href="{{ route('employees.show', ['employee' => $employee, 'tab' => 'notes']) }}" class="{{ $tab === 'notes' ? 'on' : '' }}">{{ __('ملاحظات') }}</a>
    </div>

    @if($tab === 'documents')
      <div class="tbl-wrap"><table>
        <tr><th>{{ __('النوع') }}</th><th>{{ __('الرقم') }}</th><th>{{ __('الجهة / الشركة') }}</th><th>{{ __('الإصدار') }}</th><th>{{ __('الانتهاء') }}</th><th></th></tr>
        @forelse($documents as $d)
          @if($editDoc === $d->id && $can)
            <tr><td colspan="6">@include('partials.doc-form', ['action' => route('employee-documents.update', $d), 'method' => 'PUT', 'types' => \App\Models\EmployeeDocument::TYPES, 'group' => 'employee_doc', 'doc' => $d, 'cancel' => route('employees.show', ['employee' => $employee, 'tab' => 'documents'])])</td></tr>
          @else
            <tr>
              <td><b>{{ $d->label() }}</b></td><td dir="ltr" style="text-align:start">{{ $d->number ?: '—' }}</td><td>{{ $d->provider ?: '—' }}</td>
              <td>{{ $d->issue_date?->fmt() ?: '—' }}</td><td><x-expiry :doc="$d"/></td>
              <td style="white-space:nowrap">@if($can)
                <a class="btn sm sec" href="{{ route('employees.show', ['employee' => $employee, 'tab' => 'documents', 'edit_doc' => $d->id]) }}" title="{{ __('تجديد / تعديل') }}"><x-icon name="edit"/></a>
                <form method="POST" action="{{ route('employee-documents.destroy', $d) }}" style="display:inline" onsubmit="return confirm('{{ __('حذف الوثيقة؟') }}')">@csrf @method('DELETE')<button class="btn sm sec" aria-label="{{ __('حذف') }}"><x-icon name="trash"/></button></form>
              @endif</td>
            </tr>
          @endif
        @empty <tr><td colspan="6" class="empty">{{ __('لا وثائق مسجّلة بعد.') }}</td></tr> @endforelse
      </table></div>
      @if($can)
        <details style="margin-top:14px" @if($documents->isEmpty()) open @endif>
          <summary class="btn sm">{{ __('إضافة وثيقة') }}</summary>
          @include('partials.doc-form', ['action' => route('employee-documents.store', $employee), 'method' => 'POST', 'types' => \App\Models\EmployeeDocument::TYPES, 'group' => 'employee_doc', 'doc' => null, 'cancel' => null])
        </details>
      @endif

    @elseif($tab === 'leaves')
      <div class="grid g4" style="margin-bottom:14px">
        @foreach($balances as $b)
          <div class="card kpi flat"><span class="ic vio"><x-icon name="calendar-off"/></span><div><b>@if($b['left'] === null)—@else{{ $n($b['left']) }}@endif</b><span>{{ $b['type']->displayName() }}@if($b['left'] === null) · {{ __('الاستحقاق غير محدد') }}@else · {{ __('متبقٍ هذا العام') }}@endif</span></div></div>
        @endforeach
      </div>
      <div class="tbl-wrap"><table>
        <tr><th>{{ __('النوع') }}</th><th>{{ __('الفترة') }}</th><th>{{ __('الأيام') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('ملاحظات') }}</th><th></th></tr>
        @forelse($leaves as $l)
          <tr class="{{ $l->status === 'cancelled' ? 'done' : '' }}">
            <td>{{ $l->type->displayName() }}</td><td>{{ $l->start_date->fmt() }} → {{ $l->end_date->fmt() }}</td><td>{{ $n($l->days) }}</td>
            <td><span class="pill {{ $l->status === 'approved' ? 'grn' : '' }}">{{ __('types.leave_status.'.$l->status) }}</span></td><td>{{ $l->reason }}</td>
            <td>@if($can)<form method="POST" action="{{ route('leaves.toggle', $l) }}">@csrf<button class="btn sm sec">{{ $l->status === 'approved' ? __('إلغاء') : __('استعادة') }}</button></form>@endif</td>
          </tr>
        @empty <tr><td colspan="6" class="empty">{{ __('لا إجازات مسجّلة.') }}</td></tr> @endforelse
      </table></div>
      @if($can)
        <details style="margin-top:14px"><summary class="btn sm">{{ __('تسجيل إجازة') }}</summary>
          <form method="POST" action="{{ route('leaves.store') }}" class="inline-form" data-leave-form>@csrf
            <input type="hidden" name="employee_id" value="{{ $employee->id }}"><input type="hidden" name="return" value="employee">
            <label>{{ __('نوع الإجازة') }}<select name="leave_type_id" required>@foreach($types as $t)<option value="{{ $t->id }}">{{ $t->displayName() }}</option>@endforeach</select></label>
            <label>{{ __('من') }}<input type="date" name="start_date" required></label><label>{{ __('إلى') }}<input type="date" name="end_date" required></label>
            <label>{{ __('عدد الأيام') }}<input type="number" step="0.5" min="0.5" name="days" dir="ltr"></label>
            <label>{{ __('ملاحظات') }}<input name="reason" maxlength="2000"></label>
            <button class="btn">{{ __('تسجيل') }}</button>
          </form>
        </details>
      @endif
    @endif
  </div>

  @if(auth()->user()->is_admin)
    <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('{{ __('حذف الموظف نهائيًا مع كل وثائقه وإجازاته؟ الأفضل تعطيله بدل الحذف.') }}')" style="margin-top:24px">@csrf @method('DELETE')
      <button class="btn sec sm"><x-icon name="trash"/> {{ __('حذف نهائي') }}</button></form>
  @endif
</div>
@include('partials.leave-days-js')
@endsection

@extends('layouts.odoo')
@section('title', $vehicle->plate.' — '.__('مركز القيادة'))
@php
    $can = auth()->user()->canManageData();
    $badDocs = $documents->filter(fn ($d) => $d->tone() !== '')->count();
    $editDoc = (int) request('edit_doc');
    $money = fn ($x) => number_format((float) $x, 2);
@endphp
@section('controlpanel')
<x-cp :crumbs="[[__('المركبات'), route('vehicles.index')], [$vehicle->title(), null]]">
  <x-slot:actions>@if($can)<button form="vf" class="btn">{{ __('حفظ') }}</button>@endif</x-slot:actions>
</x-cp>
@endsection
@section('content')
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="o-sheet">
  <div class="o-smart">
    <a href="{{ route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'documents']) }}" class="{{ $badDocs ? 'warn-s' : '' }}"><x-icon name="file"/><span><b>{{ $documents->count() }}</b><small>{{ __('الوثائق') }}@if($badDocs) · {{ $badDocs }} ⚠@endif</small></span></a>
    <a href="{{ route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'records']) }}"><x-icon name="wrench"/><span><b>{{ $records->count() }}</b><small>{{ __('السجلات') }}</small></span></a>
    <a href="{{ route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'records']) }}"><x-icon name="coins"/><span><b>{{ $money($total) }}</b><small>{{ __('إجمالي التكاليف') }}</small></span></a>
  </div>

  <form id="vf" method="POST" action="{{ route('vehicles.update', $vehicle) }}">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="hidden" name="status" value="{{ $vehicle->status }}">
    <div class="o-statusbar">
      @foreach(\App\Models\Vehicle::STATUSES as $st)
        @if($can && $vehicle->status !== $st)<button formaction="{{ route('vehicles.status', $vehicle) }}" name="status" value="{{ $st }}">{{ __('types.vehicle_status.'.$st) }}</button>
        @else<span class="{{ $vehicle->status === $st ? 'on' : '' }}">{{ __('types.vehicle_status.'.$st) }}</span>@endif
      @endforeach
    </div>
    @include('vehicles._fields')
    @if($tab === 'notes')
      <div class="o-f full" style="margin-top:14px"><label>{{ __('ملاحظات') }}</label><textarea name="notes" rows="6" @disabled(! $can)>{{ old('notes', $vehicle->notes) }}</textarea></div>
    @endif
  </form>

  <div class="o-notebook">
    <div class="o-tabs">
      <a href="{{ route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'documents']) }}" class="{{ $tab === 'documents' ? 'on' : '' }}">{{ __('الوثائق (الاستمارة والتأمين…)') }}</a>
      <a href="{{ route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'records']) }}" class="{{ $tab === 'records' ? 'on' : '' }}">{{ __('السجلات (صيانة، مخالفات…)') }}</a>
      <a href="{{ route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'notes']) }}" class="{{ $tab === 'notes' ? 'on' : '' }}">{{ __('ملاحظات') }}</a>
    </div>

    @if($tab === 'documents')
      <div class="tbl-wrap"><table>
        <tr><th>{{ __('النوع') }}</th><th>{{ __('الرقم') }}</th><th>{{ __('الجهة / الشركة') }}</th><th>{{ __('الإصدار') }}</th><th>{{ __('الانتهاء') }}</th><th></th></tr>
        @forelse($documents as $d)
          @if($editDoc === $d->id && $can)
            <tr><td colspan="6">@include('partials.doc-form', ['action' => route('vehicle-documents.update', $d), 'method' => 'PUT', 'types' => \App\Models\VehicleDocument::TYPES, 'group' => 'vehicle_doc', 'doc' => $d, 'cancel' => route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'documents'])])</td></tr>
          @else
            <tr><td><b>{{ $d->label() }}</b></td><td dir="ltr" style="text-align:start">{{ $d->number ?: '—' }}</td><td>{{ $d->provider ?: '—' }}</td><td>{{ $d->issue_date?->fmt() ?: '—' }}</td><td><x-expiry :doc="$d"/></td>
              <td style="white-space:nowrap">@if($can)
                <a class="btn sm sec" href="{{ route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'documents', 'edit_doc' => $d->id]) }}" title="{{ __('تجديد / تعديل') }}"><x-icon name="edit"/></a>
                <form method="POST" action="{{ route('vehicle-documents.destroy', $d) }}" style="display:inline" onsubmit="return confirm('{{ __('حذف الوثيقة؟') }}')">@csrf @method('DELETE')<button class="btn sm sec" aria-label="{{ __('حذف') }}"><x-icon name="trash"/></button></form>
              @endif</td></tr>
          @endif
        @empty <tr><td colspan="6" class="empty">{{ __('لا وثائق مسجّلة بعد.') }}</td></tr> @endforelse
      </table></div>
      @if($can)<details style="margin-top:14px" @if($documents->isEmpty()) open @endif><summary class="btn sm">{{ __('إضافة وثيقة') }}</summary>
        @include('partials.doc-form', ['action' => route('vehicle-documents.store', $vehicle), 'method' => 'POST', 'types' => \App\Models\VehicleDocument::TYPES, 'group' => 'vehicle_doc', 'doc' => null, 'cancel' => null])</details>@endif

    @elseif($tab === 'records')
      <div class="tbl-wrap"><table>
        <tr><th>{{ __('التاريخ') }}</th><th>{{ __('النوع') }}</th><th>{{ __('الوصف') }}</th><th>{{ __('الجهة / الورشة') }}</th><th>{{ __('العداد (كم)') }}</th><th>{{ __('المبلغ') }}</th><th></th></tr>
        @forelse($records as $r)
          <tr><td>{{ $r->record_date->fmt() }}</td><td><span class="pill {{ in_array($r->type, ['fine', 'accident']) ? 'red' : 'blu' }}">{{ __('types.vehicle_record.'.$r->type) }}</span></td><td>{{ $r->description }}</td><td>{{ $r->vendor ?: '—' }}</td><td>{{ $r->odometer !== null ? number_format($r->odometer) : '—' }}</td><td>{{ $r->amount !== null ? $money($r->amount) : '—' }}</td>
            <td>@if($can)<form method="POST" action="{{ route('vehicle-records.destroy', $r) }}" onsubmit="return confirm('{{ __('حذف السجل؟') }}')">@csrf @method('DELETE')<button class="btn sm sec" aria-label="{{ __('حذف') }}"><x-icon name="trash"/></button></form>@endif</td></tr>
        @empty <tr><td colspan="7" class="empty">{{ __('لا سجلات بعد.') }}</td></tr> @endforelse
        @if($records->isNotEmpty())<tr><td colspan="5"><b>{{ __('الإجمالي') }}</b></td><td><b>{{ $money($total) }}</b></td><td></td></tr>@endif
      </table></div>
      @if($can)<details style="margin-top:14px" @if($records->isEmpty()) open @endif><summary class="btn sm">{{ __('إضافة سجل') }}</summary>
        <form method="POST" action="{{ route('vehicle-records.store', $vehicle) }}" class="inline-form">@csrf
          <label>{{ __('النوع') }}<select name="type" required>@foreach(\App\Models\VehicleRecord::TYPES as $t)<option value="{{ $t }}">{{ __('types.vehicle_record.'.$t) }}</option>@endforeach</select></label>
          <label>{{ __('التاريخ') }}<input type="date" name="record_date" value="{{ today()->format('Y-m-d') }}" required></label>
          <label>{{ __('العداد (كم)') }}<input type="number" name="odometer" min="0" dir="ltr"></label>
          <label>{{ __('المبلغ') }}<input type="number" step="0.01" min="0" name="amount" dir="ltr"></label>
          <label>{{ __('الجهة / الورشة') }}<input name="vendor"></label>
          <label>{{ __('الوصف') }}<input name="description" maxlength="2000"></label>
          <button class="btn">{{ __('إضافة') }}</button>
        </form></details>@endif
    @endif
  </div>

  @if(auth()->user()->is_admin)
    <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" onsubmit="return confirm('{{ __('حذف المركبة نهائيًا مع كل وثائقها وسجلاتها؟') }}')" style="margin-top:24px">@csrf @method('DELETE')
      <button class="btn sec sm"><x-icon name="trash"/> {{ __('حذف نهائي') }}</button></form>
  @endif
</div>
@endsection

@extends('layouts.odoo')
@section('title', __('سجل الموظفين').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('الموظفون'), route('employees.index')], [__('سجل الموظفين'), null]]">
  <x-slot:actions>
    <button class="btn" onclick="window.print()"><x-icon name="file"/> {{ __('طباعة') }}</button>
    <a class="btn sec" href="{{ route('employees.export', ['status' => $status]) }}"><x-icon name="download"/> {{ __('تصدير CSV') }}</a>
  </x-slot:actions>
  <x-slot:right>
    <form method="GET"><select name="status" onchange="this.form.submit()" aria-label="{{ __('الحالة') }}"><option value="active" @selected($status === 'active')>{{ __('على رأس العمل') }}</option><option value="inactive" @selected($status === 'inactive')>{{ __('غير نشط') }}</option><option value="all" @selected($status === 'all')>{{ __('الكل') }}</option></select></form>
    <span class="date">{{ $rows->count() }} {{ __('موظف') }}</span>
  </x-slot:right>
</x-cp>
@endsection
@section('content')
<div class="o-list tbl-wrap"><table>
  <thead><tr><th>#</th><th>{{ __('الاسم') }}</th><th>{{ __('الشركة') }}</th><th>{{ __('رقم الموظف') }}</th><th>{{ __('المسمى الوظيفي') }}</th><th>{{ __('الجنسية') }}</th><th>{{ __('تاريخ التعيين') }}</th><th>{{ __('الجوال') }}</th><th>{{ __('الإقامة') }}</th><th>{{ __('التأمين الطبي') }}</th><th>{{ __('جواز السفر') }}</th><th>{{ __('العقد') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
  <tbody>
  @forelse($rows as $i => $r)
    @php $e = $r['employee']; $d = $r['docs']; @endphp
    <tr class="link" onclick="location='{{ route('employees.show', $e) }}'">
      <td>{{ $i + 1 }}</td><td><a href="{{ route('employees.show', $e) }}"><b>{{ $e->displayName() }}</b></a></td><td>{{ $e->company?->displayName() }}</td><td>{{ $e->code ?: '—' }}</td><td>{{ $e->job_title ?: '—' }}</td><td>{{ $e->nationality ?: '—' }}</td><td>{{ $e->hire_date?->fmt() ?: '—' }}</td><td dir="ltr" style="text-align:start">{{ $e->phone ?: '—' }}</td>
      <td>@if($d['iqama'])<span dir="ltr">{{ $d['iqama']->number }}</span><br><x-expiry :doc="$d['iqama']"/>@else — @endif</td>
      <td>@if($d['insurance']){{ $d['insurance']->provider }}<br><x-expiry :doc="$d['insurance']"/>@else — @endif</td>
      <td>@if($d['passport'])<x-expiry :doc="$d['passport']"/>@else — @endif</td>
      <td>@if($d['contract'])<x-expiry :doc="$d['contract']"/>@else — @endif</td>
      <td>{{ __('types.employee_status.'.$e->status) }}</td>
    </tr>
  @empty<tr><td colspan="13" class="empty">{{ __('لا يوجد موظفون مطابقون.') }}</td></tr>@endforelse
  </tbody>
</table></div>
@endsection

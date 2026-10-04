@extends('layouts.odoo')
@section('title', __('حسابات الدخول').' — '.__('مركز القيادة'))
@section('controlpanel')
<x-cp :crumbs="[[__('حسابات الدخول'), null]]">
  <x-slot:actions><a class="btn" href="{{ route('users.create') }}"><x-icon name="plus"/> {{ __('مستخدم جديد') }}</a></x-slot:actions>
  <x-slot:right><span class="date">{{ $users->count() }} {{ __('مستخدم') }}</span></x-slot:right>
</x-cp>
@endsection
@section('content')
@foreach($errors->all() as $e)<div class="warn">{{ $e }}</div>@endforeach
<div class="o-list tbl-wrap"><table>
  <thead><tr><th></th><th>{{ __('الاسم') }}</th><th>{{ __('البريد الإلكتروني') }}</th><th>{{ __('الصلاحيات') }}</th><th>{{ __('الشركات') }}</th><th>{{ __('الحالة') }}</th><th></th></tr></thead>
  <tbody>
  @foreach($users as $u)
    @php $preset = $u->is_admin ? 'admin' : \App\Support\Permissions::presetOf($u->permissions ?? []); @endphp
    <tr class="link" onclick="location='{{ route('users.show', $u) }}'">
      <td><span class="av sm">{{ mb_substr($u->name, 0, 1) }}</span></td>
      <td><a href="{{ route('users.show', $u) }}"><b>{{ $u->name }}</b></a>@if($u->is(auth()->user())) <span class="tag">{{ __('أنت') }}</span>@endif</td>
      <td dir="ltr" style="text-align:start">{{ $u->email }}</td>
      <td><span class="pill {{ $preset === 'admin' ? 'vio' : ($preset === 'custom' ? 'amb' : 'blu') }}">{{ $preset === 'admin' ? __('مدير النظام') : __('types.preset.'.$preset) }}</span></td>
      <td>@if($u->is_admin || $u->all_companies)<span class="pill pri">{{ __('كل الشركات') }}</span>@else @forelse($u->companies as $c)<span class="pill">{{ $c->displayName() }}</span> @empty<span class="pill red">{{ __('لا شركات') }}</span>@endforelse @endif</td>
      <td><span class="pill {{ $u->is_active ? 'grn' : 'red' }}">{{ $u->is_active ? __('نشط') : __('موقوف') }}</span></td>
      <td onclick="event.stopPropagation()">
        <form method="POST" action="{{ route('users.toggle', $u) }}">@csrf<button class="btn sm sec" @disabled($u->is(auth()->user()))>{{ $u->is_active ? __('إيقاف') : __('تفعيل') }}</button></form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table></div>
@endsection

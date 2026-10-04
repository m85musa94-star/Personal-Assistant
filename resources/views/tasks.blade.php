@extends('layouts.odoo')
@section('title', __('المهام والجدولة').' — '.__('مركز القيادة'))
@section('controlpanel')
<div class="o-cp" id="cp"></div>
@endsection
@section('content')
<div id="app"></div>
<dialog id="dlg"></dialog>
<div id="toast"></div>
<form id="logoutForm" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
@php
    $u = auth()->user();
    $markazUser = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'admin' => $u->is_admin ? true : false, 'hr' => false, 'locale' => $uiLocale];
@endphp
<script>window.MARKAZ_USER = {{ \Illuminate\Support\Js::from($markazUser) }};</script>
<script src="{{ asset('app/i18n.js') }}?v={{ filemtime(public_path('app/i18n.js')) }}"></script>
<script src="{{ asset('app/app.js') }}?v={{ filemtime(public_path('app/app.js')) }}"></script>
@endsection

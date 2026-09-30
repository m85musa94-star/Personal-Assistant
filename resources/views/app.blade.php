@extends('layouts.base')
@section('title', __('مركز القيادة'))
@section('body')
<div id="app"></div>
<dialog id="dlg"></dialog>
<div id="toast"></div>
<form id="logoutForm" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
@php
    $u = auth()->user();
    $markazUser = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'admin' => $u->is_admin ? true : false, 'hr' => $u->isHrManager(), 'locale' => $uiLocale];
@endphp
<script>window.MARKAZ_USER = {{ \Illuminate\Support\Js::from($markazUser) }};</script>
<script src="{{ asset('app/i18n.js') }}?v={{ filemtime(public_path('app/i18n.js')) }}"></script>
<script src="{{ asset('app/app.js') }}?v={{ filemtime(public_path('app/app.js')) }}"></script>
@endsection

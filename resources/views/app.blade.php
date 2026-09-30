<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0f766e">
<title>مركز القيادة</title>
<link rel="manifest" href="{{ asset('app/manifest.webmanifest') }}">
<link rel="stylesheet" href="{{ asset('app/style.css') }}?v={{ filemtime(public_path('app/style.css')) }}">
</head>
<body>
<div id="app"></div>
<dialog id="dlg"></dialog>
<div id="toast"></div>
<form id="logoutForm" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
@php
    $u = auth()->user();
    $markazUser = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'admin' => $u->is_admin ? true : false];
@endphp
<script>window.MARKAZ_USER = {{ \Illuminate\Support\Js::from($markazUser) }};</script>
<script src="{{ asset('app/app.js') }}?v={{ filemtime(public_path('app/app.js')) }}"></script>
</body>
</html>

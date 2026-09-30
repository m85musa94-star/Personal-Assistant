<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0f766e">
<title>@yield('title', 'مركز القيادة')</title>
<link rel="stylesheet" href="{{ asset('app/style.css') }}?v={{ filemtime(public_path('app/style.css')) }}">
<script>try{var t=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body class="plain">
<div class="plainbox @yield('wide')">
@yield('content')
</div>
</body>
</html>

<!doctype html>
<html lang="{{ $uiLocale }}" dir="{{ $uiDir }}" data-theme="{{ $uiTheme }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0d9488">
<title>@yield('title', __('مركز القيادة'))</title>
<script>
(function(){var d=document.documentElement,t=d.dataset.theme;
if(t==='auto'){t=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';d.dataset.theme=t}
window.markazToggleTheme=function(){var n=d.dataset.theme==='dark'?'light':'dark';d.dataset.theme=n;
fetch('/preferences',{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({theme:n})}).catch(function(){})}})();
</script>
<link rel="stylesheet" href="{{ asset('app/style.css') }}?v={{ filemtime(public_path('app/style.css')) }}">
@stack('head')
</head>
<body>
@include('partials.sprite')
@yield('body')
</body>
</html>

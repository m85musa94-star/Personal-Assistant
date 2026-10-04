<!doctype html>
<html lang="{{ $uiLocale }}" dir="{{ $uiDir }}" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title')</title>
<link rel="stylesheet" href="{{ asset('app/style.css') }}?v={{ filemtime(public_path('app/style.css')) }}">
</head>
<body class="print-body">
@include('partials.sprite')
<div class="noprint"><button class="btn" onclick="window.print()"><x-icon name="file"/> {{ __('طباعة / حفظ PDF') }}</button><a class="btn sec" href="javascript:history.back()">{{ __('رجوع') }}</a></div>
@yield('content')
<p class="date" style="margin-top:24px">{{ __('طُبع في') }} {{ now()->fmt('j M Y H:i') }} — {{ auth()->user()->name }}</p>
</body>
</html>

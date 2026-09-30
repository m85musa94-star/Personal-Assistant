@extends('layouts.base')
@section('body')
<div class="app">
  @include('partials.sidebar')
  <main class="main">
    <header class="bar">
      <div class="q"></div>
      <span class="date">{{ now()->locale($uiLocale)->translatedFormat('l j F Y') }}</span>
      @include('partials.prefs')
    </header>
    @if(session('ok'))<div class="okm">{{ session('ok') }}</div>@endif
    @if(session('warn'))<div class="warn">{{ session('warn') }}</div>@endif
    @yield('content')
  </main>
</div>
@endsection

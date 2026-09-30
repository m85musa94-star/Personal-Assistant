@extends('layouts.base')
@section('body')
@php
    $u = auth()->user();
    $apps = \App\Support\Apps::all($u);
    $appKey = \App\Support\Apps::currentKey(request()->path());
    $app = $appKey ? $apps[$appKey] : null;
    $companies = \App\Support\CompanyContext::all();
    $cc = \App\Support\CompanyContext::current();
    $alerts = \App\Support\Alerts::counts();
    $path = request()->path();
    $isOn = function (array $m) use ($path) {
        if (! isset($m['match'])) {
            return request()->fullUrl() === $m['url'];
        }
        if ($m['match'] === 'alerts' && request()->query('scope')) { return false; }
        return $path === $m['match'] || str_starts_with($path, $m['match'].'/');
    };
@endphp
<header class="o-navbar">
  <a class="o-st o-apps" href="{{ route('home') }}" title="{{ __('التطبيقات') }}" aria-label="{{ __('التطبيقات') }}"><x-icon name="dots"/></a>
  @if($app)
    <a class="o-brand" href="{{ $app['url'] }}">{{ $app['label'] }}</a>
    <nav class="o-menus">
      @foreach($app['menus'] as $m)
        <a href="{{ $m['url'] }}" class="{{ $isOn($m) ? 'on' : '' }}" @isset($m['view']) data-view="{{ $m['view'] }}" @endisset>{{ $m['label'] }}</a>
      @endforeach
    </nav>
  @else
    <span class="o-brand">{{ __('مركز القيادة') }}</span><span class="o-menus"></span>
  @endif
  <div class="o-systray">
    @if($companies->isNotEmpty())
    <details class="o-dd">
      <summary class="o-st" title="{{ __('الشركة') }}"><x-icon name="building"/><span class="nm">{{ $cc?->displayName() ?? __('كل الشركات') }}</span><x-icon name="chevron"/></summary>
      <div class="o-dd-menu">
        <div class="muted">{{ __('الشركة الحالية') }}</div>
        <form method="POST" action="{{ route('company.switch') }}">@csrf
          <button name="company" value="all" class="{{ $cc ? '' : 'on' }}"><x-icon name="grid"/> {{ __('كل الشركات') }}</button>
          @foreach($companies as $c)
            <button name="company" value="{{ $c->id }}" class="{{ $cc?->id === $c->id ? 'on' : '' }}"><x-icon name="building"/> {{ $c->displayName() }}@unless($c->is_active) <small>({{ __('معطّلة') }})</small>@endunless</button>
          @endforeach
        </form>
      </div>
    </details>
    @endif
    <a class="o-st" href="{{ route('alerts.index') }}" title="{{ __('التنبيهات') }}" aria-label="{{ __('التنبيهات') }}"><x-icon name="bell"/>@if($alerts['total'])<span class="o-badge {{ $alerts['expired'] ? 'red' : '' }}">{{ $alerts['total'] }}</span>@endif</a>
    @include('partials.prefs')
    <details class="o-dd">
      <summary class="o-st" title="{{ $u->name }}"><span class="av nav">{{ mb_substr($u->name, 0, 1) }}</span></summary>
      <div class="o-dd-menu">
        <div class="muted"><b style="color:var(--tx)">{{ $u->name }}</b><br><span dir="ltr">{{ $u->email }}</span></div><hr>
        <a href="{{ route('account') }}"><x-icon name="user"/> {{ __('حسابي') }}</a>
        @if($u->is_admin)<a href="{{ route('users.index') }}"><x-icon name="lock"/> {{ __('حسابات الدخول') }}</a>@endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button><x-icon name="logout"/> {{ __('تسجيل الخروج') }}</button></form>
      </div>
    </details>
  </div>
</header>
@yield('controlpanel')
<main class="o-content">
  @if(session('ok'))<div class="okm">{{ session('ok') }}</div>@endif
  @if(session('warn'))<div class="warn">{{ session('warn') }}</div>@endif
  @yield('content')
</main>
<script>document.addEventListener('click',function(e){document.querySelectorAll('details.o-dd[open]').forEach(function(d){if(!d.contains(e.target))d.removeAttribute('open')})});</script>
@endsection

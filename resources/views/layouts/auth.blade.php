@extends('layouts.base')
@section('body')
<div class="authwrap">
  <div class="authbox">
    <div class="authtop"><span class="brand" style="padding:0"><span class="logo"><x-icon name="check"/></span>{{ __('مركز القيادة') }}</span><span>@include('partials.prefs')</span></div>
    @yield('content')
  </div>
</div>
@endsection

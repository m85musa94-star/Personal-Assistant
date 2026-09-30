@extends('layouts.shell')
@section('title', __('الهيكل التنظيمي').' — '.__('مركز القيادة'))
@section('content')
<div class="page-h"><h1>{{ __('الهيكل التنظيمي') }}</h1></div>
<div class="card tree" style="overflow-x:auto">
  @if($roots->isEmpty())<div class="empty">{{ __('لا بيانات بعد') }}</div>
  @else<ul>@foreach($roots as $r)@include('hr.partials.org-node', ['e' => $r, 'byManager' => $byManager, 'depth' => 0])@endforeach</ul>@endif
</div>
@endsection

@props(['doc'])
@php $d = $doc->daysLeft(); $tone = $doc->tone(); @endphp
@if($doc->expiry_date)
  <span class="pill {{ $tone }}" title="{{ $doc->expiry_date->fmt() }}">
    {{ $doc->expiry_date->fmt() }}
    @if($d < 0) · {{ __('منتهية منذ :n يوم', ['n' => abs($d)]) }}@elseif($d === 0) · {{ __('تنتهي اليوم') }}@elseif($tone) · {{ __('بعد :n يوم', ['n' => $d]) }}@endif
  </span>
@else
  <span class="pill">—</span>
@endif

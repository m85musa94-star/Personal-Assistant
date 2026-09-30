@props(['crumbs' => []])
<div class="o-cp">
  <div class="o-cp-row">
    <div class="o-bc">
      @foreach($crumbs as $i => $c)
        @if($i)<span class="sep">/</span>@endif
        @if(! empty($c[1]))<a href="{{ $c[1] }}">{{ $c[0] }}</a>@else<span>{{ $c[0] }}</span>@endif
      @endforeach
    </div>
    @isset($actions)<div class="acts">{{ $actions }}</div>@endisset
    <span class="sp"></span>
    @isset($right)<div class="acts">{{ $right }}</div>@endisset
  </div>
  @isset($search)<div class="o-cp-row">{{ $search }}</div>@endisset
</div>

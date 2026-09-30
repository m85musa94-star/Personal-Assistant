@if ($paginator->hasPages())
<nav style="display:flex;gap:6px;flex-wrap:wrap;align-items:center" aria-label="pagination">
  @if ($paginator->onFirstPage())<span class="btn sec sm" style="opacity:.4">‹</span>@else<a class="btn sec sm" href="{{ $paginator->previousPageUrl() }}">‹</a>@endif
  @foreach ($elements as $element)
    @if (is_string($element))<span class="date">{{ $element }}</span>@endif
    @if (is_array($element))
      @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())<span class="btn sm">{{ $page }}</span>@else<a class="btn sec sm" href="{{ $url }}">{{ $page }}</a>@endif
      @endforeach
    @endif
  @endforeach
  @if ($paginator->hasMorePages())<a class="btn sec sm" href="{{ $paginator->nextPageUrl() }}">›</a>@else<span class="btn sec sm" style="opacity:.4">›</span>@endif
</nav>
@endif

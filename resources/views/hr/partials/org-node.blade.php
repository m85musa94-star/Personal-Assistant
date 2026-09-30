@php $kids = $byManager[$e->id] ?? collect(); @endphp
<li>
  <a class="node" href="{{ route('hr.employees.show', $e) }}" style="color:inherit;text-decoration:none">
    <x-avatar :employee="$e"/><span><b>{{ $e->displayName() }}</b><br><small class="date">{{ $e->job_title ?: '—' }}@if($e->department) · {{ $e->department->displayName() }}@endif</small></span>
  </a>
  @if($kids->isNotEmpty() && $depth < 12)
    <ul>@foreach($kids as $k)@include('hr.partials.org-node', ['e' => $k, 'byManager' => $byManager, 'depth' => $depth + 1])@endforeach</ul>
  @endif
</li>

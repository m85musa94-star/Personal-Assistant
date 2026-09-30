@props(['employee', 'size' => ''])
<span class="av {{ $size }}" title="{{ $employee->displayName() }}">{{ $employee->initial() }}</span>

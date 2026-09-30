@props(['status'])
@php
    $map = ['pending' => ['amb', 'قيد الانتظار'], 'approved' => ['grn', 'معتمدة'], 'rejected' => ['red', 'مرفوضة'], 'cancelled' => ['', 'ملغاة']];
    [$cls, $label] = $map[$status] ?? ['', $status];
@endphp
<span class="pill {{ $cls }}">{{ __($label) }}</span>

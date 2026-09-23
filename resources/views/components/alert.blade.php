@props(['type' => 'success'])

@php
    $styles = [
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'mb-4 rounded-md border px-4 py-3 text-sm '.($styles[$type] ?? $styles['success'])]) }}>
    {{ $slot }}
</div>

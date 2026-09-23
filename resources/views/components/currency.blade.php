@props(['amount', 'tone' => 'default', 'size' => 'md'])
@php
    $tones = [
        'default' => 'text-slate-900',
        'muted' => 'text-slate-500',
        'success' => 'text-emerald-700',
        'warning' => 'text-amber-700',
        'danger' => 'text-red-700',
        'info' => 'text-blue-700',
    ];
    $sizes = ['sm' => 'text-sm', 'md' => 'text-base', 'lg' => 'text-xl'];
@endphp
<span {{ $attributes->merge(['class' => 'tabular-nums font-semibold '.($tones[$tone] ?? $tones['default']).' '.($sizes[$size] ?? $sizes['md'])]) }}>{{ number_format($amount, 2) }}</span>

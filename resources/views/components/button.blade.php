@props(['variant' => 'primary', 'size' => 'md', 'tag' => 'button', 'type' => 'submit'])

@php
    $variants = [
        'primary' => 'bg-brand-700 text-white border border-brand-700 hover:bg-brand-800 focus-visible:ring-brand-500',
        'accent' => 'bg-accent-500 text-white border border-accent-500 hover:bg-accent-600 focus-visible:ring-accent-500',
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 focus-visible:ring-slate-400',
        'danger' => 'bg-white text-red-700 border border-slate-300 hover:bg-red-50 hover:border-red-300 focus-visible:ring-red-500',
        'danger-solid' => 'bg-red-600 text-white border border-red-600 hover:bg-red-700 focus-visible:ring-red-500',
        'ghost' => 'bg-transparent text-slate-500 border border-transparent hover:text-slate-900 hover:bg-slate-100 focus-visible:ring-slate-400',
    ];
    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-3.5 py-2 text-sm',
    ];
    $classes = 'inline-flex items-center justify-center gap-1.5 rounded-md font-medium shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed '
        .($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($tag === 'a')
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif

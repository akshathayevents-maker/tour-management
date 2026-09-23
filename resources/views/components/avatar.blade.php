@props(['name', 'size' => 'md'])

@php
    $initials = collect(explode(' ', trim($name)))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('');
    $sizes = ['sm' => 'h-7 w-7 text-xs', 'md' => 'h-9 w-9 text-sm', 'lg' => 'h-12 w-12 text-base'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-brand-100 text-brand-700 font-semibold shrink-0 '.($sizes[$size] ?? $sizes['md'])]) }}>
    {{ strtoupper($initials) }}
</span>

@props(['color' => 'slate', 'label'])

@php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-600',
        'blue' => 'bg-blue-100 text-blue-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'purple' => 'bg-purple-100 text-purple-800',
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.($colors[$color] ?? $colors['slate'])]) }}>
    {{ $label }}
</span>

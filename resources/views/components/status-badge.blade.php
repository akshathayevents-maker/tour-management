@props(['color' => 'slate', 'label'])

@php
    $colors = [
        'slate' => ['bg-slate-100 text-slate-600', 'bg-slate-400'],
        'blue' => ['bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20', 'bg-blue-500'],
        'amber' => ['bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20', 'bg-amber-500'],
        'indigo' => ['bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20', 'bg-indigo-500'],
        'purple' => ['bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20', 'bg-purple-500'],
        'green' => ['bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20', 'bg-emerald-500'],
        'red' => ['bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20', 'bg-red-500'],
    ];
    [$classes, $dot] = $colors[$color] ?? $colors['slate'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium '.$classes]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
    {{ $label }}
</span>

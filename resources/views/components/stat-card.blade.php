@props(['label', 'value', 'tone' => 'default', 'hint' => null, 'icon' => null])

@php
    $tones = [
        'default' => ['text-slate-900', 'bg-slate-100 text-slate-500'],
        'success' => ['text-emerald-700', 'bg-emerald-50 text-emerald-600'],
        'warning' => ['text-amber-700', 'bg-amber-50 text-amber-600'],
        'danger' => ['text-red-700', 'bg-red-50 text-red-600'],
        'info' => ['text-blue-700', 'bg-blue-50 text-blue-600'],
        'brand' => ['text-brand-800', 'bg-brand-50 text-brand-600'],
    ];
    [$valueTone, $iconTone] = $tones[$tone] ?? $tones['default'];

    $icons = [
        'leads' => 'M13.5 2.25a.75.75 0 01.671.415l1.036 2.073 2.286.332a.75.75 0 01.416 1.279l-1.655 1.613.391 2.276a.75.75 0 01-1.088.79L13.5 9.876l-2.057 1.152a.75.75 0 01-1.088-.79l.39-2.276L9.09 6.35a.75.75 0 01.416-1.28l2.286-.331 1.037-2.073a.75.75 0 01.671-.415zM3 13.5a.75.75 0 01.75-.75h9a.75.75 0 010 1.5h-9A.75.75 0 013 13.5zm0 4.5a.75.75 0 01.75-.75h5.25a.75.75 0 010 1.5H3.75A.75.75 0 013 18z',
        'chat' => 'M4.804 21.644A6.707 6.707 0 006 21.75a33.23 33.23 0 0011.64-2.087c1.226-.463 2.109-1.522 2.109-2.827V6.187c0-1.478-1.1-2.72-2.575-2.857a48.99 48.99 0 00-11.348 0C4.35 3.467 3.25 4.71 3.25 6.187v10.75c0 1.478 1.1 2.72 2.575 2.857.42.038.842.07 1.267.096l-2.288 1.754z',
        'alert' => 'M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z',
        'clock' => 'M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5zM12.75 7.5a.75.75 0 00-1.5 0v5c0 .27.144.518.378.651l3.5 2a.75.75 0 00.744-1.302l-3.122-1.784V7.5z',
    ];
@endphp

<div class="bg-white rounded-xl border border-slate-200/80 p-4">
    <div class="flex items-center justify-between">
        <p class="text-[12.5px] font-medium text-slate-500">{{ $label }}</p>
        @if ($icon && isset($icons[$icon]))
            <span class="flex h-7 w-7 items-center justify-center rounded-md {{ $iconTone }}">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="{{ $icons[$icon] }}" clip-rule="evenodd" /></svg>
            </span>
        @endif
    </div>
    <p class="mt-2 text-[26px] leading-none font-semibold tabular-nums {{ $valueTone }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1.5 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>

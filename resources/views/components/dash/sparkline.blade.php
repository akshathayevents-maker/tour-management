@props(['values' => [], 'color' => '#38bdf8', 'height' => 40])

@php
    $max = max(1, max($values));
    $w = 100;
    $h = $height;
    $n = max(1, count($values) - 1);
    $pts = collect($values)->values()->map(fn ($v, $i) => [
        $i / $n * $w,
        $h - ($v / $max * ($h - 4)) - 2,
    ]);
    $line = $pts->map(fn ($p) => round($p[0], 2) . ',' . round($p[1], 2))->implode(' ');
    $area = 'M0,' . $h . ' L' . $line . ' L' . $w . ',' . $h . ' Z';
    $isEmpty = $max <= 1 && collect($values)->sum() === 0;
    $gid = 'spark' . substr(md5($line . $color), 0, 8);
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="w-full" style="height: {{ $h }}px">
    @if ($isEmpty)
        <line x1="0" y1="{{ $h - 3 }}" x2="{{ $w }}" y2="{{ $h - 3 }}" stroke="var(--dash-border-strong)" stroke-width="1.5" stroke-dasharray="3,3" />
    @else
        <defs>
            <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.35" />
                <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0" />
            </linearGradient>
        </defs>
        <path d="{{ $area }}" fill="url(#{{ $gid }})" />
        <polyline points="{{ $line }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    @endif
</svg>

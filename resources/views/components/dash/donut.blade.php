@props(['segments' => [], 'centerValue' => 0, 'centerLabel' => ''])

@php
    $total = max(1, array_sum(array_column($segments, 'value')));
    $radius = 15.5;
    $circumference = 2 * M_PI * $radius;
    $offset = 0;
    $isEmpty = array_sum(array_column($segments, 'value')) === 0;
@endphp

<div class="relative inline-flex items-center justify-center" style="width: 84px; height: 84px">
    <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
        <circle cx="18" cy="18" r="{{ $radius }}" fill="none" stroke="var(--dash-surface-2)" stroke-width="3.5" />
        @if (! $isEmpty)
            @foreach ($segments as $segment)
                @php
                    $fraction = $segment['value'] / $total;
                    $dash = $fraction * $circumference;
                @endphp
                @if ($segment['value'] > 0)
                    <circle cx="18" cy="18" r="{{ $radius }}" fill="none" stroke="{{ $segment['color'] }}" stroke-width="3.5"
                            stroke-dasharray="{{ round($dash, 2) }} {{ round($circumference - $dash, 2) }}"
                            stroke-dashoffset="{{ round(-$offset, 2) }}" stroke-linecap="round" />
                    @php $offset += $dash; @endphp
                @endif
            @endforeach
        @endif
    </svg>
    <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-base font-semibold leading-none" style="color: var(--dash-text)">{{ $centerValue }}</span>
        @if ($centerLabel)
            <span class="mt-0.5 text-[9px] uppercase tracking-wide" style="color: var(--dash-text-3)">{{ $centerLabel }}</span>
        @endif
    </div>
</div>

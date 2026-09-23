@props(['title', 'subtitle' => null])

<div class="flex flex-wrap justify-between items-center gap-3 mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endif
</div>

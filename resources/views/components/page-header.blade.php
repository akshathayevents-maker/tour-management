@props(['title', 'subtitle' => null])

<div class="flex flex-wrap justify-between items-center gap-3 mb-6">
    <div>
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endif
</div>

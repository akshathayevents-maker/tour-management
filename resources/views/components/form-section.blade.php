@props(['title', 'description' => null, 'number' => null])
<div class="border-b border-slate-100 py-6 first:pt-0 last:border-0 last:pb-0 grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-4">
    <div class="lg:col-span-1">
        <div class="flex items-baseline gap-2">
            @if ($number)
                <span class="text-xs font-semibold text-accent-500 tabular-nums">{{ $number }}</span>
            @endif
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
        </div>
        @if ($description)
            <p class="mt-1 text-xs text-slate-500">{{ $description }}</p>
        @endif
    </div>
    <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
        {{ $slot }}
    </div>
</div>

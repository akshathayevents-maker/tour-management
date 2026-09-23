@props(['title', 'description' => null, 'actionLabel' => null, 'actionUrl' => null])

<div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
    <h3 class="text-sm font-medium text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}"
           class="mt-4 inline-flex items-center rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
            {{ $actionLabel }}
        </a>
    @endif
</div>

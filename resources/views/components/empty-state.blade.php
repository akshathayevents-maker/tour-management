@props(['title', 'description' => null, 'actionLabel' => null, 'actionUrl' => null])

<div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/60 p-10 text-center">
    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375C2.754 3.75 2.25 4.254 2.25 4.875v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
    </div>
    <h3 class="mt-3 text-sm font-medium text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">{{ $description }}</p>
    @endif
    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}"
           class="mt-4 inline-flex items-center rounded-md bg-brand-700 text-white text-sm font-medium px-4 py-2 hover:bg-brand-800 shadow-sm">
            {{ $actionLabel }}
        </a>
    @endif
</div>

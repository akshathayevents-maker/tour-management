@props(['title'])
<div class="bg-white rounded-xl border border-slate-200/80 p-5">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">{{ $title }}</p>
    {{ $slot }}
</div>

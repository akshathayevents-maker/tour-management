@props(['title'])
<div class="bg-white rounded-lg border border-slate-200">
    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $title }}</h3>
        @isset($actions){{ $actions }}@endisset
    </div>
    <div class="p-4">{{ $slot }}</div>
</div>

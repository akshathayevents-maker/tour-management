@props(['label'])
<div class="flex items-center justify-between py-1.5 text-sm gap-4">
    <dt class="text-slate-500 shrink-0">{{ $label }}</dt>
    <dd class="text-slate-900 font-medium text-right truncate">{{ $slot }}</dd>
</div>

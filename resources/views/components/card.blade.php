@props(['title' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-slate-200 '.($padding ? 'p-5' : '')]) }}>
    @if ($title)
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            @isset($actions){{ $actions }}@endisset
        </div>
    @endif
    {{ $slot }}
</div>

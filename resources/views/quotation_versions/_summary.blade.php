{{-- Expects $quotation (with versions.lineItems loaded) --}}
@php $latest = $quotation->latestVersion(); @endphp

<div class="flex items-center justify-between mb-2">
    <a href="{{ route('quotation-versions.show', $latest) }}" class="text-sm font-medium text-slate-900 hover:underline">
        Version {{ $latest->version_number }}
    </a>
    <x-status-badge :color="$latest->status->badgeColor()" :label="$latest->isExpired() ? 'Expired' : $latest->status->label()" />
</div>
<p class="text-sm text-slate-500">Selling price: <span class="text-slate-900 font-medium">{{ number_format($latest->totalSellPrice(), 2) }}</span></p>

@if ($quotation->versions->count() > 1)
    <div class="mt-3 pt-3 border-t border-slate-100 space-y-1">
        <p class="text-xs text-slate-400">Version history</p>
        @foreach ($quotation->versions as $version)
            <a href="{{ route('quotation-versions.show', $version) }}"
               class="flex justify-between text-xs text-slate-500 hover:text-slate-900 hover:underline">
                <span>V{{ $version->version_number }}</span>
                <span>{{ number_format($version->totalSellPrice(), 2) }} &middot; {{ $version->status->label() }}</span>
            </a>
        @endforeach
    </div>
@endif

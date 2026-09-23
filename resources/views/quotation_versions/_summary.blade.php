{{-- Expects $quotation (with versions.lineItems loaded) --}}
@php $latest = $quotation->latestVersion(); @endphp

<div class="flex items-center justify-between mb-2">
    <a href="{{ route('quotation-versions.show', $latest) }}" class="text-sm font-medium text-slate-900 hover:text-brand-700">
        Version {{ $latest->version_number }}
    </a>
    <x-status-badge :color="$latest->status->badgeColor()" :label="$latest->isExpired() ? 'Expired' : $latest->status->label()" />
</div>
<div class="flex items-center gap-1.5 text-sm text-slate-500">
    Selling price <x-currency :amount="$latest->totalSellPrice()" size="sm" />
</div>

@if ($quotation->versions->count() > 1)
    <div class="mt-3 pt-3 border-t border-slate-100 space-y-1">
        <p class="text-xs text-slate-400">Version history</p>
        @foreach ($quotation->versions as $version)
            <a href="{{ route('quotation-versions.show', $version) }}"
               class="flex justify-between text-xs text-slate-500 hover:text-slate-900">
                <span>V{{ $version->version_number }}</span>
                <span class="tabular-nums">{{ number_format($version->totalSellPrice(), 2) }} &middot; {{ $version->status->label() }}</span>
            </a>
        @endforeach
    </div>
@endif

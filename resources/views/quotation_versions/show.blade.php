@extends('layouts.app')

@php
    $trip = $quotationVersion->quotation->trip;
    $acceptedVersion = $quotationVersion->quotation->acceptedVersion();
    $isViewingOlderVersion = $acceptedVersion && $acceptedVersion->id !== $quotationVersion->id
        && $quotationVersion->status !== \App\Enums\QuotationVersionStatus::Draft;
@endphp

@section('title', "Quotation V{$quotationVersion->version_number}")

@section('content')
    <x-page-header subtitle="For {{ $trip->destination }} · {{ $trip->customer->name }}">
        <x-slot:title>
            <span class="flex items-center gap-2.5 flex-wrap">
                Quotation V{{ $quotationVersion->version_number }}
                <x-status-badge :color="$quotationVersion->status->badgeColor()" :label="$quotationVersion->isExpired() ? 'Expired' : $quotationVersion->status->label()" />
            </span>
        </x-slot:title>
        <x-slot:actions>
            @if ($quotationVersion->status !== \App\Enums\QuotationVersionStatus::Draft)
                <x-button tag="a" href="{{ route('quotation-versions.print', $quotationVersion) }}" target="_blank" variant="secondary">Print</x-button>
            @endif
            <x-button tag="a" href="{{ route('trips.show', $trip) }}" variant="secondary">Back to trip</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @if ($isViewingOlderVersion)
        <x-alert type="info">
            You're viewing <strong>V{{ $quotationVersion->version_number }}</strong> ({{ $quotationVersion->status->label() }}).
            The currently accepted version is
            <a href="{{ route('quotation-versions.show', $acceptedVersion) }}" class="underline font-medium">V{{ $acceptedVersion->version_number }}</a>.
        </x-alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-summary-panel title="Overview">
                <dl class="divide-y divide-slate-50">
                    <x-detail-row label="Customer"><a href="{{ route('customers.show', $trip->customer) }}" class="hover:underline">{{ $trip->customer->name }}</a></x-detail-row>
                    <x-detail-row label="Trip"><a href="{{ route('trips.show', $trip) }}" class="hover:underline">{{ $trip->destination }}</a></x-detail-row>
                    <x-detail-row label="Version"><span class="text-slate-500">V{{ $quotationVersion->version_number }} of {{ $quotationVersion->quotation->versions->count() }}</span></x-detail-row>
                    <x-detail-row label="Status"><x-status-badge :color="$quotationVersion->status->badgeColor()" :label="$quotationVersion->isExpired() ? 'Expired' : $quotationVersion->status->label()" /></x-detail-row>
                    @if ($quotationVersion->sent_at)
                        <x-detail-row label="Sent">{{ $quotationVersion->sent_at->format('d M Y') }}</x-detail-row>
                    @endif
                    @if ($quotationVersion->valid_until)
                        <x-detail-row label="Valid until">{{ $quotationVersion->valid_until->format('d M Y') }}</x-detail-row>
                    @endif
                </dl>
            </x-summary-panel>

            <x-summary-panel title="Line items">

                @forelse ($quotationVersion->lineItems as $item)
                    <div class="flex items-start justify-between gap-2 py-2 border-b border-slate-50 last:border-0 text-sm">
                        <div class="flex-1 min-w-0">
                            <span class="text-slate-900">{{ $item->description }}</span>
                            @if ($item->category)
                                <span class="text-xs text-slate-400">&middot; {{ $item->category->label() }}</span>
                            @endif
                            @if ($item->cost !== null)
                                <p class="text-xs text-slate-400">Cost: {{ number_format($item->cost, 2) }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-slate-900 font-medium tabular-nums">{{ number_format($item->sell_price, 2) }}</span>
                            @if ($quotationVersion->isEditable())
                                <form method="POST" action="{{ route('quotation-line-items.destroy', $item) }}" onsubmit="return confirm('Remove this line item?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-red-600" aria-label="Delete">&times;</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No line items yet.</p>
                @endforelse

                @if ($quotationVersion->isEditable())
                    <form method="POST" action="{{ route('quotation-line-items.store', $quotationVersion) }}" class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                            <input type="text" name="description" placeholder="Description" required
                                   class="sm:col-span-2 rounded-md border-slate-300 shadow-sm text-sm">
                            <select name="category" class="rounded-md border-slate-300 shadow-sm text-sm">
                                <option value="">Category (optional)</option>
                                @foreach (\App\Enums\QuotationLineCategory::cases() as $category)
                                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" min="0" name="sell_price" placeholder="Selling price" required
                                   class="rounded-md border-slate-300 shadow-sm text-sm">
                        </div>
                        <details class="text-xs">
                            <summary class="cursor-pointer text-slate-500">+ Cost (optional, internal only)</summary>
                            <input type="number" step="0.01" min="0" name="cost" placeholder="Cost"
                                   class="mt-2 w-40 rounded-md border-slate-300 shadow-sm text-sm">
                        </details>
                        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
                            Add line item
                        </button>
                    </form>
                @endif

                <div class="mt-4 pt-4 border-t border-slate-200 text-sm space-y-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Total selling price</span>
                        <x-currency :amount="$quotationVersion->totalSellPrice()" />
                    </div>
                    @if ($quotationVersion->totalCost() !== null)
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Total cost</span>
                            <x-currency :amount="$quotationVersion->totalCost()" tone="muted" size="sm" />
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Estimated margin</span>
                            <x-currency :amount="$quotationVersion->estimatedMargin()" tone="success" size="sm" />
                        </div>
                    @endif
                </div>
            </x-summary-panel>

            @if ($quotationVersion->isEditable())
                <x-summary-panel title="Terms">
                    <form method="POST" action="{{ route('quotation-versions.update', $quotationVersion) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <x-field label="Valid until (optional)" name="valid_until">
                            <input id="valid_until" type="date" name="valid_until" value="{{ old('valid_until', optional($quotationVersion->valid_until)->format('Y-m-d')) }}"
                                   class="block w-full sm:w-60 rounded-md border-slate-300 shadow-sm sm:text-sm">
                        </x-field>
                        <x-field label="Terms (optional)" name="terms">
                            <textarea id="terms" name="terms" rows="3"
                                      class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('terms', $quotationVersion->terms) }}</textarea>
                        </x-field>
                        <x-button type="submit" variant="secondary">Save</x-button>
                    </form>
                </x-summary-panel>
            @elseif ($quotationVersion->terms || $quotationVersion->valid_until)
                <x-summary-panel title="Terms">
                    @if ($quotationVersion->valid_until)
                        <p class="text-sm text-slate-500">Valid until {{ $quotationVersion->valid_until->format('d M Y') }}</p>
                    @endif
                    @if ($quotationVersion->terms)
                        <p class="text-sm text-slate-600 mt-1">{{ $quotationVersion->terms }}</p>
                    @endif
                </x-summary-panel>
            @endif
        </div>

        <div class="space-y-6">
            <x-summary-panel title="Actions">
                <div class="space-y-2">

                @if ($quotationVersion->isEditable())
                    <form method="POST" action="{{ route('quotation-versions.send', $quotationVersion) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-brand-700 text-white text-sm font-medium px-4 py-2 hover:bg-brand-800">
                            Send to customer
                        </button>
                    </form>
                @endif

                @if ($quotationVersion->status === \App\Enums\QuotationVersionStatus::Sent)
                    <form method="POST" action="{{ route('quotation-versions.accept', $quotationVersion) }}"
                          onsubmit="return confirm('Mark V{{ $quotationVersion->version_number }} as accepted?{{ $acceptedVersion ? ' This will supersede the currently accepted V'.$acceptedVersion->version_number.'.' : '' }}');">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-emerald-600 text-white text-sm font-medium px-4 py-2 hover:bg-emerald-700">
                            Mark accepted
                        </button>
                    </form>
                    <form method="POST" action="{{ route('quotation-versions.reject', $quotationVersion) }}"
                          onsubmit="return confirm('Mark V{{ $quotationVersion->version_number }} as rejected? This cannot be undone.');">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-white border border-red-200 text-red-700 text-sm font-medium px-4 py-2 hover:bg-red-50">
                            Mark rejected
                        </button>
                    </form>
                @endif

                @if (! $quotationVersion->isEditable())
                    <form method="POST" action="{{ route('quotation-versions.new-version', $quotationVersion) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                            Create a revision
                        </button>
                    </form>
                    @if ($quotationVersion->status === \App\Enums\QuotationVersionStatus::Accepted)
                        <p class="text-xs text-slate-400">Creates a new draft copied from this version. If the new draft is accepted, it will supersede V{{ $quotationVersion->version_number }}.</p>
                    @endif
                @endif

                @if ($quotationVersion->status === \App\Enums\QuotationVersionStatus::Accepted)
                    @if ($existingBooking)
                        <a href="{{ route('bookings.show', $existingBooking) }}"
                           class="block w-full text-center rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                            View booking
                        </a>
                    @else
                        <form method="POST" action="{{ route('quotation-versions.booking.store', $quotationVersion) }}">
                            @csrf
                            <button type="submit" class="w-full rounded-md bg-brand-700 text-white text-sm font-medium px-4 py-2 hover:bg-brand-800">
                                Convert V{{ $quotationVersion->version_number }} to a booking
                            </button>
                        </form>
                    @endif
                @endif
                </div>
            </x-summary-panel>

            @if ($quotationVersion->quotation->versions->count() > 1)
                <x-summary-panel title="Version history">
                    @foreach ($quotationVersion->quotation->versions as $version)
                        <a href="{{ route('quotation-versions.show', $version) }}"
                           class="flex items-center justify-between gap-2 py-2 px-2 -mx-2 rounded-md text-sm min-w-0 {{ $version->id === $quotationVersion->id ? 'bg-brand-50 text-slate-900 font-medium' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                            <span class="flex items-center gap-1.5 min-w-0 shrink-0">
                                V{{ $version->version_number }}
                                @if ($version->id === $quotationVersion->id)
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-brand-700 whitespace-nowrap">Viewing</span>
                                @endif
                            </span>
                            <span class="flex items-center gap-2 min-w-0 justify-end">
                                <span class="tabular-nums truncate">{{ number_format($version->totalSellPrice(), 2) }}</span>
                                <x-status-badge :color="$version->status->badgeColor()" :label="$version->isExpired() ? 'Expired' : $version->status->label()" />
                            </span>
                        </a>
                    @endforeach
                </x-summary-panel>
            @endif

            {{-- Activity --}}
            @if ($activities->isNotEmpty())
                <x-summary-panel title="Activity">
                    <div class="relative">
                        @foreach ($activities as $activity)
                            <div class="flex gap-3 pb-3 last:pb-0">
                                <div class="flex flex-col items-center shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-400 mt-1.5"></span>
                                    @if (! $loop->last)<span class="w-px flex-1 bg-slate-100 mt-1"></span>@endif
                                </div>
                                <div class="flex-1 min-w-0 text-sm">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-600">{{ $activity->description }}</span>
                                        <span class="text-xs text-slate-400 shrink-0">{{ $activity->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if ($activity->causer)
                                        <span class="text-xs text-slate-400">by {{ $activity->causer->name }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-summary-panel>
            @endif
        </div>
    </div>
@endsection

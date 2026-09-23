@extends('layouts.app')

@php $trip = $quotationVersion->quotation->trip; @endphp

@section('title', "Quotation V{$quotationVersion->version_number}")

@section('content')
    <x-page-header :title="'Quotation V'.$quotationVersion->version_number" subtitle="For {{ $trip->destination }} · {{ $trip->customer->name }}">
        <x-slot:actions>
            <a href="{{ route('trips.show', $trip) }}"
               class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                Back to trip
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-sm font-medium text-slate-900">Line items</h3>
                    <x-status-badge :color="$quotationVersion->status->badgeColor()" :label="$quotationVersion->isExpired() ? 'Expired' : $quotationVersion->status->label()" />
                </div>

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

                <div class="mt-4 pt-4 border-t border-slate-200 text-sm space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Total selling price</span>
                        <span class="text-slate-900 font-semibold">{{ number_format($quotationVersion->totalSellPrice(), 2) }}</span>
                    </div>
                    @if ($quotationVersion->totalCost() !== null)
                        <div class="flex justify-between text-slate-500">
                            <span>Total cost</span>
                            <span>{{ number_format($quotationVersion->totalCost(), 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Estimated margin</span>
                            <span>{{ number_format($quotationVersion->estimatedMargin(), 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if ($quotationVersion->isEditable())
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <h3 class="text-sm font-medium text-slate-900 mb-3">Terms</h3>
                    <form method="POST" action="{{ route('quotation-versions.update', $quotationVersion) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="valid_until" class="block text-sm font-medium text-slate-700">Valid until (optional)</label>
                            <input id="valid_until" type="date" name="valid_until" value="{{ old('valid_until', optional($quotationVersion->valid_until)->format('Y-m-d')) }}"
                                   class="mt-1 block w-full sm:w-60 rounded-md border-slate-300 shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label for="terms" class="block text-sm font-medium text-slate-700">Terms (optional)</label>
                            <textarea id="terms" name="terms" rows="3"
                                      class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('terms', $quotationVersion->terms) }}</textarea>
                        </div>
                        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                            Save
                        </button>
                    </form>
                </div>
            @elseif ($quotationVersion->terms || $quotationVersion->valid_until)
                <div class="bg-white rounded-lg border border-slate-200 p-5 text-sm">
                    <h3 class="text-sm font-medium text-slate-900 mb-2">Terms</h3>
                    @if ($quotationVersion->valid_until)
                        <p class="text-slate-500">Valid until {{ $quotationVersion->valid_until->format('d M Y') }}</p>
                    @endif
                    @if ($quotationVersion->terms)
                        <p class="text-slate-600 mt-1">{{ $quotationVersion->terms }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5 space-y-2">
                <h3 class="text-sm font-medium text-slate-900 mb-1">Actions</h3>

                @if ($quotationVersion->isEditable())
                    <form method="POST" action="{{ route('quotation-versions.send', $quotationVersion) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                            Send to customer
                        </button>
                    </form>
                @endif

                @if ($quotationVersion->status === \App\Enums\QuotationVersionStatus::Sent)
                    <form method="POST" action="{{ route('quotation-versions.accept', $quotationVersion) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-green-600 text-white text-sm font-medium px-4 py-2 hover:bg-green-700">
                            Mark accepted
                        </button>
                    </form>
                    <form method="POST" action="{{ route('quotation-versions.reject', $quotationVersion) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                            Mark rejected
                        </button>
                    </form>
                @endif

                @if (! $quotationVersion->isEditable())
                    <form method="POST" action="{{ route('quotation-versions.new-version', $quotationVersion) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                            Create new version
                        </button>
                    </form>
                @endif
            </div>

            @if ($quotationVersion->quotation->versions->count() > 1)
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <h3 class="text-sm font-medium text-slate-900 mb-3">Version history</h3>
                    @foreach ($quotationVersion->quotation->versions as $version)
                        <a href="{{ route('quotation-versions.show', $version) }}"
                           class="flex justify-between items-center py-1.5 text-sm {{ $version->id === $quotationVersion->id ? 'text-slate-900 font-medium' : 'text-slate-500 hover:text-slate-900' }}">
                            <span>V{{ $version->version_number }}</span>
                            <span>{{ number_format($version->totalSellPrice(), 2) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

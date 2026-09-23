@extends('layouts.app')

@section('title', 'Booking #'.$booking->id)

@section('content')
    <x-page-header title="Booking #{{ $booking->id }}" subtitle="{{ $booking->trip->destination }} · {{ $booking->customer->name }}">
        <x-slot:actions>
            @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                <form method="POST" action="{{ route('bookings.complete', $booking) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                        Mark completed
                    </button>
                </form>
                <details class="relative">
                    <summary class="list-none cursor-pointer rounded-md bg-white border border-slate-300 text-red-700 text-sm font-medium px-4 py-2 hover:bg-red-50">
                        Cancel booking
                    </summary>
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                          class="absolute right-0 mt-1 w-64 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                        @csrf
                        <input name="cancellation_reason" placeholder="Reason (optional)"
                               class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                        <button type="submit" class="w-full rounded-md bg-red-600 text-white text-xs font-medium px-3 py-1.5 hover:bg-red-700">
                            Confirm cancellation
                        </button>
                    </form>
                </details>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="flex items-center gap-3 mb-4">
        <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" />
        @if ($booking->isInProgress())
            <x-status-badge color="amber" label="Trip in progress" />
        @endif
        @if ($booking->acceptedQuotationVersion)
            <a href="{{ route('quotation-versions.show', $booking->acceptedQuotationVersion) }}" class="text-xs text-slate-400 hover:underline">
                From Quotation V{{ $booking->acceptedQuotationVersion->version_number }}
            </a>
        @endif
    </div>

    @if ($booking->status === \App\Enums\BookingStatus::Cancelled && $booking->cancellation_reason)
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            Cancelled: {{ $booking->cancellation_reason }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Trip Readiness --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Trip readiness</h3>
                <dl class="space-y-1.5 text-sm">
                    @foreach ($booking->readinessRows() as $row)
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500">{{ $row['label'] }}</dt>
                            <dd class="{{ $row['ok'] ? 'text-green-700' : 'text-amber-700' }} font-medium">
                                {{ $row['ok'] ? '✓' : '⚠' }} {{ $row['state'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Quotation reference (if converted from one) --}}
            @if (! $booking->isOperationallyLocked() && $booking->acceptedQuotationVersion && $booking->acceptedQuotationVersion->lineItems->isNotEmpty())
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <h3 class="text-sm font-medium text-slate-900 mb-1">From the quotation</h3>
                    <p class="text-xs text-slate-400 mb-3">Convert a line into a service below, or skip it and add services manually.</p>
                    @foreach ($booking->acceptedQuotationVersion->lineItems as $line)
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-50 last:border-0 text-sm">
                            <span class="text-slate-700">{{ $line->description }} &mdash; {{ number_format($line->sell_price, 2) }}</span>
                            <form method="POST" action="{{ route('booking-items.store', $booking) }}">
                                @csrf
                                <input type="hidden" name="description" value="{{ $line->description }}">
                                <input type="hidden" name="category" value="{{ $line->category?->value }}">
                                <input type="hidden" name="sell_price" value="{{ $line->sell_price }}">
                                <button type="submit" class="text-xs rounded-md border border-slate-300 px-2 py-1 text-slate-600 hover:bg-slate-50">
                                    Convert to service
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Booking items --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Services</h3>

                @forelse ($booking->items as $item)
                    @include('booking_items._item', ['item' => $item, 'locked' => $booking->isOperationallyLocked()])
                @empty
                    <p class="text-sm text-slate-400">No services added yet.</p>
                @endforelse

                @unless ($booking->isOperationallyLocked())
                    <form method="POST" action="{{ route('booking-items.store', $booking) }}" class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <input type="text" name="description" placeholder="Add a service…" required
                                   class="sm:col-span-2 rounded-md border-slate-300 shadow-sm text-sm">
                            <select name="category" class="rounded-md border-slate-300 shadow-sm text-sm">
                                <option value="">Category (optional)</option>
                                @foreach (\App\Enums\QuotationLineCategory::cases() as $category)
                                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
                            Add service
                        </button>
                    </form>
                @else
                    <p class="text-xs text-slate-400 mt-3 pt-3 border-t border-slate-100">
                        This booking is {{ strtolower($booking->status->label()) }} — services are read-only.
                    </p>
                @endunless
            </div>

            {{-- Activity --}}
            @if ($activities->isNotEmpty())
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <h3 class="text-sm font-medium text-slate-900 mb-3">Activity</h3>
                    @foreach ($activities as $activity)
                        <div class="flex justify-between text-sm py-1 border-b border-slate-50 last:border-0">
                            <span class="text-slate-600">{{ $activity->description }}</span>
                            <span class="text-xs text-slate-400">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Overview</h3>
                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-slate-500">Customer</dt>
                    <dd class="text-slate-900"><a href="{{ route('customers.show', $booking->customer) }}" class="hover:underline">{{ $booking->customer->name }}</a></dd>
                    <dt class="text-slate-500">Trip</dt>
                    <dd class="text-slate-900"><a href="{{ route('trips.show', $booking->trip) }}" class="hover:underline">{{ $booking->trip->destination }}</a></dd>
                    <dt class="text-slate-500">Dates</dt>
                    <dd class="text-slate-900">
                        {{ $booking->trip->start_date?->format('d M Y') ?? '—' }}
                        @if ($booking->trip->end_date) &ndash; {{ $booking->trip->end_date->format('d M Y') }} @endif
                    </dd>
                </dl>
            </div>

            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Checklist</h3>

                @foreach (\App\Enums\ChecklistPhase::cases() as $phase)
                    @php $items = $booking->checklistItems->where('phase', $phase); @endphp
                    @if ($items->isNotEmpty())
                        <p class="text-xs font-medium text-slate-400 mt-3 first:mt-0">{{ $phase->label() }}</p>
                        @foreach ($items as $item)
                            @include('booking_checklist._item', ['item' => $item])
                        @endforeach
                    @endif
                @endforeach

                <form method="POST" action="{{ route('booking-checklist-items.store', $booking) }}" class="flex gap-2 mt-3 pt-3 border-t border-slate-100">
                    @csrf
                    <input type="text" name="label" placeholder="Add a checklist item…" required
                           class="flex-1 rounded-md border-slate-300 shadow-sm text-sm">
                    <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
                        Add
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

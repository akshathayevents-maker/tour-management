@extends('layouts.app')

@section('title', $trip->destination)

@section('content')
    {{-- Journey hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <x-status-badge :color="$trip->status->badgeColor()" :label="$trip->status->label()" class="!bg-white/10 !text-white !ring-white/20" />
                </div>
                <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">{{ $trip->destination }}</h2>
                <p class="mt-1 text-sm text-brand-200">
                    For <a href="{{ route('customers.show', $trip->customer) }}" class="text-white hover:underline">{{ $trip->customer->name }}</a>
                </p>
            </div>
            <x-button tag="a" href="{{ route('trips.edit', $trip) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">Edit trip</x-button>
        </div>
        <div class="relative mt-5 flex flex-wrap gap-x-8 gap-y-3 border-t border-white/10 pt-4">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Travel dates</p>
                <p class="text-sm font-medium text-white mt-0.5">
                    {{ $trip->start_date?->format('d M Y') ?? 'Not set' }}
                    @if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} @endif
                </p>
            </div>
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Travellers</p>
                <p class="text-sm font-medium text-white mt-0.5">{{ $trip->travellers_count ?? '—' }}</p>
            </div>
            @if ($trip->enquiry)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Originating enquiry</p>
                    <a href="{{ route('enquiries.show', $trip->enquiry) }}" class="text-sm font-medium text-accent-400 hover:text-accent-300 mt-0.5 inline-block">{{ $trip->enquiry->destination }}</a>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Itinerary --}}
            <x-summary-panel title="Itinerary">
                @if (! $trip->itinerary)
                    <x-empty-state
                        title="No itinerary yet."
                        description="Itinerary is optional — add one if you want a day-by-day plan."
                        action-label="Create itinerary" />
                    <form method="POST" action="{{ route('trips.itinerary.store', $trip) }}" class="mt-3">
                        @csrf
                        <x-button type="submit">Create itinerary</x-button>
                    </form>
                @else
                    @foreach ($trip->itinerary->days as $day)
                        @include('itineraries._day', ['day' => $day])
                    @endforeach

                    @php $nextDay = ($trip->itinerary->days->max('day_number') ?? 0) + 1; @endphp
                    <form method="POST" action="{{ route('itinerary-days.store', $trip->itinerary) }}" class="flex gap-2 mt-3 pt-3 border-t border-slate-100">
                        @csrf
                        <input type="hidden" name="day_number" value="{{ $nextDay }}">
                        <x-button type="submit" variant="secondary">+ Add day {{ $nextDay }}</x-button>
                    </form>
                @endif
            </x-summary-panel>

            {{-- Quotation --}}
            <x-summary-panel title="Quotation">
                @if (! $trip->quotation)
                    <x-empty-state
                        title="No quotation yet."
                        description="Create one when you're ready to price this trip." />
                    <form method="POST" action="{{ route('trips.quotation.store', $trip) }}" class="mt-3">
                        @csrf
                        <x-button type="submit">Create quotation</x-button>
                    </form>
                @else
                    @include('quotation_versions._summary', ['quotation' => $trip->quotation])
                @endif
            </x-summary-panel>

            {{-- Booking --}}
            @php $booking = $trip->activeBooking(); @endphp
            <x-summary-panel title="Booking">
                <x-slot:actions>
                    @if ($booking)
                        <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" />
                    @endif
                </x-slot:actions>

                @if (! $booking)
                    <x-empty-state
                        title="No booking yet."
                        description="Create one directly, or from an accepted quotation version." />
                    <form method="POST" action="{{ route('bookings.store') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ $trip->customer_id }}">
                        <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                        <x-button type="submit">Create booking</x-button>
                    </form>
                @else
                    <a href="{{ route('bookings.show', $booking) }}" class="text-sm text-slate-900 font-medium hover:underline">
                        View booking workspace &rarr;
                    </a>
                    <div class="mt-3 space-y-1 text-sm">
                        @foreach ($booking->readinessRows() as $row)
                            <div class="flex justify-between">
                                <span class="text-slate-500">{{ $row['label'] }}</span>
                                <span class="{{ $row['ok'] ? 'text-green-700' : 'text-amber-700' }}">{{ $row['ok'] ? '✓' : '⚠' }} {{ $row['state'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-summary-panel>
        </div>

        <div class="space-y-6">
            <x-summary-panel title="Follow-ups">
                @include('follow_ups._quick-add', ['subjectType' => 'trip', 'subjectId' => $trip->id])
                @forelse ($followUps as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp, 'hideSubject' => true])
                @empty
                    <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                @endforelse
            </x-summary-panel>
        </div>
    </div>
@endsection

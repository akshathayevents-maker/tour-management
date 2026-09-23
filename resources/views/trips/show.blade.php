@extends('layouts.app')

@section('title', $trip->destination)

@section('content')
    <x-page-header :title="$trip->destination" subtitle="For {{ $trip->customer->name }}">
        <x-slot:actions>
            <a href="{{ route('trips.edit', $trip) }}"
               class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                Edit
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Overview --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-sm font-medium text-slate-900">Overview</h3>
                    <x-status-badge :color="$trip->status->badgeColor()" :label="$trip->status->label()" />
                </div>
                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-slate-500">Customer</dt>
                    <dd class="text-slate-900"><a href="{{ route('customers.show', $trip->customer) }}" class="hover:underline">{{ $trip->customer->name }}</a></dd>
                    <dt class="text-slate-500">Travel dates</dt>
                    <dd class="text-slate-900">
                        {{ $trip->start_date?->format('d M Y') ?? '—' }}
                        @if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} @endif
                    </dd>
                    <dt class="text-slate-500">Travellers</dt>
                    <dd class="text-slate-900">{{ $trip->travellers_count ?? '—' }}</dd>
                    <dt class="text-slate-500">Enquiry</dt>
                    <dd class="text-slate-900">
                        @if ($trip->enquiry)
                            <a href="{{ route('enquiries.show', $trip->enquiry) }}" class="hover:underline">{{ $trip->enquiry->destination }}</a>
                        @else — @endif
                    </dd>
                </dl>
            </div>

            {{-- Itinerary --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Itinerary</h3>

                @if (! $trip->itinerary)
                    <x-empty-state
                        title="No itinerary yet."
                        description="Itinerary is optional — add one if you want a day-by-day plan."
                        action-label="Create itinerary" />
                    <form method="POST" action="{{ route('trips.itinerary.store', $trip) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                            Create itinerary
                        </button>
                    </form>
                @else
                    @foreach ($trip->itinerary->days as $day)
                        @include('itineraries._day', ['day' => $day])
                    @endforeach

                    @php $nextDay = ($trip->itinerary->days->max('day_number') ?? 0) + 1; @endphp
                    <form method="POST" action="{{ route('itinerary-days.store', $trip->itinerary) }}" class="flex gap-2 mt-3 pt-3 border-t border-slate-100">
                        @csrf
                        <input type="hidden" name="day_number" value="{{ $nextDay }}">
                        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
                            + Add day {{ $nextDay }}
                        </button>
                    </form>
                @endif
            </div>

            {{-- Quotation --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Quotation</h3>

                @if (! $trip->quotation)
                    <x-empty-state
                        title="No quotation yet."
                        description="Create one when you're ready to price this trip." />
                    <form method="POST" action="{{ route('trips.quotation.store', $trip) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                            Create quotation
                        </button>
                    </form>
                @else
                    @include('quotation_versions._summary', ['quotation' => $trip->quotation])
                @endif
            </div>

            {{-- Booking --}}
            @php $booking = $trip->activeBooking(); @endphp
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-sm font-medium text-slate-900">Booking</h3>
                    @if ($booking)
                        <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" />
                    @endif
                </div>

                @if (! $booking)
                    <x-empty-state
                        title="No booking yet."
                        description="Create one directly, or from an accepted quotation version." />
                    <form method="POST" action="{{ route('bookings.store') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ $trip->customer_id }}">
                        <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                        <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                            Create booking
                        </button>
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
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Follow-ups</h3>
                @include('follow_ups._quick-add', ['subjectType' => 'trip', 'subjectId' => $trip->id])
                @forelse ($followUps as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp, 'hideSubject' => true])
                @empty
                    <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

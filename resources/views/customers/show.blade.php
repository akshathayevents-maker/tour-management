@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    @php
        $destinationsVisited = $trips->filter(fn ($t) => $t->status === \App\Enums\TripStatus::Completed)->pluck('destination')->unique()->values();
        $upcomingDestinations = $trips->filter(fn ($t) => $t->status !== \App\Enums\TripStatus::Cancelled && $t->start_date && $t->start_date->isFuture())->pluck('destination')->unique()->values();
    @endphp

    {{-- Profile header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-5">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-avatar :name="$customer->name" size="lg" class="ring-2 ring-white/10 !bg-white/10 !text-white shrink-0" />
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">{{ $customer->name }}</h2>
                        <span class="text-xs font-medium text-brand-300">#{{ $customer->id }}</span>
                    </div>
                    <p class="mt-1 text-sm text-brand-200">Customer since {{ $customer->created_at->format('d M Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button tag="a" href="{{ route('bookings.create', ['customer_id' => $customer->id]) }}" size="sm" variant="accent">New booking</x-button>
                <x-button tag="a" href="{{ route('enquiries.create', ['customer_id' => $customer->id]) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">New enquiry</x-button>
                <x-button tag="a" href="{{ route('customers.edit', $customer) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">Edit</x-button>
            </div>
        </div>

        <div class="relative mt-5 flex flex-wrap gap-x-8 gap-y-3 border-t border-white/10 pt-4">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Phone</p>
                <p class="text-sm font-medium text-white mt-0.5">{{ $customer->phone }}</p>
            </div>
            @if ($customer->email)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Email</p>
                    <p class="text-sm font-medium text-white mt-0.5">{{ $customer->email }}</p>
                </div>
            @endif
            @if ($customer->city || $customer->country)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Location</p>
                    <p class="text-sm font-medium text-white mt-0.5">{{ collect([$customer->city, $customer->country])->filter()->join(', ') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Relationship summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <x-stat-card label="Enquiries" :value="$customer->enquiries->count()" icon="chat" />
        <x-stat-card label="Trips" :value="$trips->count()" icon="leads" :hint="$trips->where('status', \App\Enums\TripStatus::Completed)->count().' completed'" />
        <x-stat-card label="Total booking value" value="{{ number_format($totalBookingValue, 0) }}" icon="clock" />
        <x-stat-card label="Outstanding balance" value="{{ number_format($outstandingBalance, 0) }}" :tone="$outstandingBalance > 0 ? 'warning' : 'success'" icon="alert" :hint="number_format($totalCollected, 0).' collected'" />
    </div>

    {{-- Section navigation --}}
    <nav class="mb-5 flex gap-1 overflow-x-auto rounded-lg border border-slate-200 bg-white p-1 text-sm">
        @foreach ([
            'overview' => 'Overview', 'enquiries' => 'Enquiries', 'trips' => 'Trips & Bookings',
            'quotations' => 'Quotations', 'payments' => 'Payments', 'followups' => 'Follow-ups', 'activity' => 'Activity',
        ] as $anchor => $label)
            <a href="#{{ $anchor }}" class="shrink-0 rounded-md px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="space-y-6">
        {{-- Overview --}}
        <section id="overview" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                @if ($customer->whatsapp || $customer->notes)
                    <x-summary-panel title="Additional details">
                        @if ($customer->whatsapp)
                            <dl class="divide-y divide-slate-50">
                                <x-detail-row label="WhatsApp">{{ $customer->whatsapp }}</x-detail-row>
                            </dl>
                        @endif
                        @if ($customer->notes)
                            <p class="{{ $customer->whatsapp ? 'mt-3 border-t border-slate-100 pt-3' : '' }} text-sm text-slate-600">{{ $customer->notes }}</p>
                        @endif
                    </x-summary-panel>
                @endif

                <x-summary-panel title="Travel profile">
                    @if ($upcomingTrip)
                        <div class="rounded-lg border border-brand-100 bg-brand-50/50 p-3.5 mb-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-700">Upcoming journey</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $upcomingTrip->destination }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $upcomingTrip->start_date?->format('d M Y') }}
                                @if ($upcomingTrip->end_date) &rarr; {{ $upcomingTrip->end_date->format('d M Y') }} @endif
                            </p>
                            @php $trackedBooking = $upcomingTrip->activeBooking(); @endphp
                            @if ($trackedBooking)
                                <div class="mt-2 flex items-center gap-2 flex-wrap">
                                    <x-status-badge :color="$trackedBooking->status->badgeColor()" :label="$trackedBooking->status->label()" />
                                    @if ($trackedBooking->amountRemaining() > 0)
                                        <span class="text-xs font-medium text-amber-700">{{ number_format($trackedBooking->amountRemaining(), 0) }} due</span>
                                    @else
                                        <span class="text-xs font-medium text-emerald-700">Paid in full</span>
                                    @endif
                                    <a href="{{ route('bookings.show', $trackedBooking) }}" class="text-xs font-medium text-brand-700 hover:underline">View booking &rarr;</a>
                                </div>
                            @else
                                <a href="{{ route('trips.show', $upcomingTrip) }}" class="mt-2 inline-block text-xs font-medium text-brand-700 hover:underline">View trip &rarr;</a>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-slate-400 mb-3">No upcoming journeys scheduled.</p>
                    @endif

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Visited destinations</p>
                            <p class="mt-1 text-slate-700">{{ $destinationsVisited->isNotEmpty() ? $destinationsVisited->join(', ') : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Upcoming destinations</p>
                            <p class="mt-1 text-slate-700">{{ $upcomingDestinations->isNotEmpty() ? $upcomingDestinations->join(', ') : '—' }}</p>
                        </div>
                    </div>
                </x-summary-panel>

                <x-summary-panel title="Recent relationship activity">
                    @forelse ($activities->take(5) as $activity)
                        <div class="flex items-start justify-between gap-3 py-2 border-b border-slate-50 last:border-0 text-sm">
                            <span class="text-slate-600">{{ $activity->description }}</span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No recorded activity yet.</p>
                    @endforelse
                </x-summary-panel>
            </div>

            <div class="space-y-6">
                @if ($customer->leads->isNotEmpty())
                    <x-summary-panel title="Originating leads">
                        @foreach ($customer->leads as $lead)
                            <a href="{{ route('leads.show', $lead) }}"
                               class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                                <span class="text-slate-900">{{ $lead->source->label() }}</span>
                                <span class="text-slate-400">{{ $lead->created_at->format('d M Y') }}</span>
                            </a>
                        @endforeach
                    </x-summary-panel>
                @endif

                <x-summary-panel title="Follow-ups">
                    @include('follow_ups._quick-add', ['subjectType' => 'customer', 'subjectId' => $customer->id])
                    @forelse ($followUps->where('status', \App\Enums\FollowUpStatus::Pending)->take(5) as $followUp)
                        @include('follow_ups._item', ['followUp' => $followUp])
                    @empty
                        <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                    @endforelse
                </x-summary-panel>
            </div>
        </section>

        {{-- Enquiries --}}
        <section id="enquiries">
            <x-summary-panel title="Enquiries ({{ $customer->enquiries->count() }})">
                @forelse ($customer->enquiries as $enquiry)
                    <a href="{{ route('enquiries.show', $enquiry) }}"
                       class="flex items-center justify-between gap-3 py-2.5 border-b border-slate-50 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900 truncate">{{ $enquiry->destination }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $enquiry->created_at->format('d M Y') }}
                                @if ($enquiry->start_date) &middot; Travel {{ $enquiry->start_date->format('d M Y') }} @endif
                                @if ($enquiry->adults) &middot; {{ $enquiry->adults + ($enquiry->children ?? 0) }} travellers @endif
                                @if ($tripByEnquiryId->has($enquiry->id)) &middot; Converted to trip @endif
                            </p>
                        </div>
                        <x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" />
                    </a>
                @empty
                    <p class="text-sm text-slate-400">No enquiries yet.</p>
                @endforelse
            </x-summary-panel>
        </section>

        {{-- Trips & bookings --}}
        <section id="trips">
            <x-summary-panel title="Trips &amp; bookings ({{ $trips->count() }})">
                @forelse ($trips as $trip)
                    <div class="py-3 border-b border-slate-50 last:border-0">
                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ route('trips.show', $trip) }}" class="font-medium text-slate-900 hover:text-brand-700 min-w-0 truncate">{{ $trip->destination }}</a>
                            <x-status-badge :color="$trip->status->badgeColor()" :label="$trip->status->label()" />
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            @if ($trip->start_date) {{ $trip->start_date->format('d M Y') }} @if ($trip->end_date) &rarr; {{ $trip->end_date->format('d M Y') }} @endif @endif
                            @if ($trip->travellers_count) &middot; {{ $trip->travellers_count }} {{ Str::plural('traveller', $trip->travellers_count) }} @endif
                            @if ($trip->itinerary) &middot; <span class="text-emerald-700 font-medium">Itinerary ready</span> @endif
                        </p>
                        @php $tripBookings = $bookings->where('trip_id', $trip->id); @endphp
                        @if ($tripBookings->isNotEmpty())
                            <div class="mt-2 space-y-1.5">
                                @foreach ($tripBookings as $booking)
                                    <a href="{{ route('bookings.show', $booking) }}" class="flex items-center justify-between gap-2 rounded-md bg-slate-50 px-2.5 py-1.5 text-xs hover:bg-slate-100">
                                        <span class="font-medium text-slate-700">Booking #{{ $booking->id }}</span>
                                        <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" />
                                        <span class="font-medium {{ $booking->amountRemaining() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                            {{ $booking->amountRemaining() > 0 ? number_format($booking->amountRemaining(), 0).' due' : 'Paid in full' }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No trips recorded yet.</p>
                @endforelse
            </x-summary-panel>
        </section>

        {{-- Quotations --}}
        <section id="quotations">
            <x-summary-panel title="Quotations ({{ $quotations->count() }})">
                @forelse ($quotations as $quotation)
                    @php $latest = $quotation->latestVersion(); @endphp
                    <div class="flex items-center justify-between gap-3 py-2.5 border-b border-slate-50 last:border-0 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900 truncate">{{ $quotation->trip->destination }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                @if ($latest) Version {{ $latest->version_number }} &middot; {{ $latest->lineItems->sum('sell_price') ? number_format($latest->lineItems->sum('sell_price'), 0) : '0' }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if ($latest)
                                <x-status-badge :color="$latest->status->badgeColor()" :label="$latest->status->label()" />
                                <a href="{{ route('quotation-versions.show', $latest) }}" class="text-xs font-medium text-brand-700 hover:underline">View &rarr;</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No quotations yet.</p>
                @endforelse
            </x-summary-panel>
        </section>

        {{-- Payments --}}
        <section id="payments">
            <x-summary-panel title="Payment history">
                <x-slot:actions>
                    <span class="text-xs text-slate-500">{{ number_format($totalCollected, 0) }} collected of {{ number_format($totalBookingValue, 0) }}</span>
                </x-slot:actions>
                @forelse ($customerPayments as $payment)
                    <div class="flex items-center justify-between gap-3 py-2.5 border-b border-slate-50 last:border-0 text-sm {{ $payment->isVoided() ? 'opacity-50' : '' }}">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900">
                                <x-currency :amount="$payment->amount" size="sm" />
                                <span class="text-xs text-slate-400 font-normal">&middot; {{ $payment->method->label() }}</span>
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $payment->paid_at->format('d M Y') }}
                                &middot; <a href="{{ route('bookings.show', $payment->booking) }}" class="hover:underline">Booking #{{ $payment->booking_id }}</a>
                                @if ($payment->reference) &middot; Ref {{ $payment->reference }} @endif
                            </p>
                        </div>
                        @if ($payment->isVoided())
                            <x-status-badge color="slate" label="Voided" />
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No payments recorded yet.</p>
                @endforelse
            </x-summary-panel>
        </section>

        {{-- Follow-ups --}}
        <section id="followups">
            <x-summary-panel title="Follow-up history ({{ $followUps->count() }})">
                @forelse ($followUps as $followUp)
                    <div class="flex items-start justify-between gap-3 py-2.5 border-b border-slate-50 last:border-0 text-sm">
                        <div class="min-w-0">
                            <p class="text-slate-900">{{ $followUp->reason ?: 'Follow-up' }}</p>
                            <p class="text-xs mt-0.5 {{ $followUp->isOverdue() ? 'text-red-600 font-medium' : 'text-slate-400' }}">
                                {{ $followUp->due_at->format('d M Y, g:i A') }}
                                @if ($followUp->isOverdue()) &middot; Overdue @endif
                            </p>
                        </div>
                        <x-status-badge
                            :color="match($followUp->status->value) { 'completed' => 'green', 'cancelled' => 'slate', default => $followUp->isOverdue() ? 'red' : 'amber' }"
                            :label="$followUp->status->label()" />
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No follow-ups recorded yet.</p>
                @endforelse
            </x-summary-panel>
        </section>

        {{-- Activity --}}
        <section id="activity">
            <x-summary-panel title="Activity history">
                @if ($activities->isNotEmpty())
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
                                    <span class="text-xs text-slate-400">by {{ $activity->causer->name ?? 'System' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-400">No recorded activity yet.</p>
                @endif
            </x-summary-panel>
        </section>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'New booking')

@section('content')
    <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-3">
        <a href="{{ route('bookings.index') }}" class="hover:text-slate-600">Bookings</a>
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        <span class="text-slate-500">New booking</span>
    </nav>

    <x-page-header title="Create a booking" subtitle="Set up a customer booking and prepare it for service planning.">
        <x-slot:actions>
            <span class="inline-flex items-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20 px-2.5 py-1 text-xs font-medium">Direct booking</span>
        </x-slot:actions>
    </x-page-header>

    @if ($customer || $trip)
        <div class="mb-5 flex flex-wrap items-center gap-2 rounded-lg bg-brand-50/60 border border-brand-100 px-4 py-2.5 text-sm">
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">Connected to</span>
            @if ($customer)
                <span class="text-slate-700">{{ $customer->name }}</span>
            @endif
            @if ($trip)
                @if ($customer)<span class="text-slate-400">&middot;</span>@endif
                <a href="{{ route('trips.show', $trip) }}" class="text-brand-700 hover:underline">{{ $trip->destination }}</a>
            @endif
        </div>
    @endif

    {{-- Mobile compact summary --}}
    <div class="lg:hidden mb-5 bg-white rounded-xl border border-slate-200/80 px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Summary</p>
            <p id="mobile-summary-text" class="text-sm text-slate-700 truncate">Select a customer and trip to preview the booking</p>
        </div>
        <span class="shrink-0 inline-flex items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 px-2 py-0.5 text-[11px] font-medium">New</span>
    </div>

    <form method="POST" action="{{ route('bookings.store') }}" class="pb-28 lg:pb-0" id="booking-form"
          onsubmit="this.querySelectorAll('button[type=submit]').forEach(b => { b.disabled = true; b.textContent = 'Creating booking…'; });"
          oninput="const cSel = document.getElementById('customer_id'); const tSel = document.getElementById('trip_id');
                   const custName = (cSel ? cSel.options[cSel.selectedIndex]?.text.split(' · ')[0] : null) || '{{ $customer?->name }}' || null;
                   const tripName = (tSel ? tSel.options[tSel.selectedIndex]?.text.split(' · ')[0] : null) || '{{ $trip?->destination }}' || null;
                   const empty = document.getElementById('summary-empty');
                   const filled = document.getElementById('summary-filled');
                   if (empty) empty.classList.toggle('hidden', !!(custName || tripName));
                   if (filled) filled.classList.toggle('hidden', !(custName || tripName));
                   document.getElementById('preview-customer').textContent = custName || 'Not selected yet';
                   document.getElementById('preview-trip').textContent = tripName || 'Not selected yet';
                   document.getElementById('mobile-summary-text').textContent = [custName, tripName].filter(Boolean).join(' · ') || 'Select a customer and trip to preview the booking';">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-8">
            {{-- Main form --}}
            <div class="lg:col-span-2 space-y-0">
                <x-card :padding="false" class="px-5 sm:px-7">
                    <x-form-section number="01" title="Customer" description="Who is this booking for?">
                        @if ($customer)
                            <div class="sm:col-span-2 flex items-center gap-2.5">
                                <x-avatar :name="$customer->name" size="sm" />
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $customer->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $customer->phone }}</p>
                                </div>
                            </div>
                            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                        @else
                            <x-field label="Customer" name="customer_id" required wide>
                                <select id="customer_id" name="customer_id" required class="form-select">
                                    <option value="">Select a customer</option>
                                    @foreach (\App\Models\Customer::orderBy('name')->get() as $option)
                                        <option value="{{ $option->id }}" @selected(old('customer_id') == $option->id)>
                                            {{ $option->name }} &middot; {{ $option->phone }}
                                        </option>
                                    @endforeach
                                </select>
                            </x-field>
                            <p class="sm:col-span-2 text-xs text-slate-400 -mt-2">
                                Don't see them? <a href="{{ route('customers.create') }}" class="text-brand-700 hover:underline">Add a new customer</a>.
                            </p>
                        @endif
                    </x-form-section>

                    <x-form-section number="02" title="Trip" description="Which journey is being booked?">
                        @if ($trip)
                            <div class="sm:col-span-2">
                                <p class="text-sm font-medium text-slate-900">{{ $trip->destination }}</p>
                                @if ($trip->start_date)
                                    <p class="text-xs text-slate-400">{{ $trip->start_date->format('d M Y') }}@if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} @endif</p>
                                @endif
                            </div>
                            <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                        @elseif (\App\Models\Trip::count() === 0)
                            <div class="sm:col-span-2">
                                <x-empty-state
                                    title="No trips yet"
                                    description="Create a trip first, then come back to book it."
                                    actionLabel="Create a trip"
                                    :actionUrl="route('trips.create')" />
                                <input type="hidden" name="trip_id" value="">
                            </div>
                        @else
                            <x-field label="Trip" name="trip_id" required wide>
                                <select id="trip_id" name="trip_id" required class="form-select">
                                    <option value="">Select a trip</option>
                                    @foreach (\App\Models\Trip::orderByDesc('id')->get() as $option)
                                        <option value="{{ $option->id }}" @selected(old('trip_id') == $option->id)>
                                            {{ $option->destination }} &middot; {{ $option->customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </x-field>
                            <p class="sm:col-span-2 text-xs text-slate-400 -mt-2">
                                Don't see it? <a href="{{ route('trips.create') }}" class="text-brand-700 hover:underline">Create a trip first</a>.
                            </p>
                        @endif
                    </x-form-section>

                    <x-form-section number="03" title="Confirmation" description="What happens when you create this booking.">
                        <div class="sm:col-span-2 flex items-start gap-2.5 rounded-lg bg-emerald-50/70 border border-emerald-100 px-3.5 py-3">
                            <svg class="h-4 w-4 shrink-0 mt-0.5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            <p class="text-sm text-slate-700">
                                The booking is created with status <span class="font-semibold text-slate-900">Confirmed</span> right away — no quotation step required.
                                A booking reference is generated automatically once saved.
                            </p>
                        </div>
                    </x-form-section>
                </x-card>

                {{-- Desktop actions --}}
                <div class="hidden lg:flex items-center justify-end gap-2 mt-5">
                    <x-button tag="a" href="{{ route('bookings.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit">Create booking</x-button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="hidden lg:block lg:sticky lg:top-20 lg:self-start space-y-4">
                <x-summary-panel title="Booking summary">
                    <div id="summary-empty" class="{{ $customer || $trip ? 'hidden' : '' }} text-center py-4">
                        <svg class="mx-auto h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        <p class="mt-2 text-sm text-slate-500">Select a customer and trip to preview the booking.</p>
                    </div>
                    <dl id="summary-filled" class="{{ $customer || $trip ? '' : 'hidden' }} divide-y divide-slate-50">
                        <x-detail-row label="Customer"><span id="preview-customer">{{ $customer?->name ?: 'Not selected yet' }}</span></x-detail-row>
                        <x-detail-row label="Trip"><span id="preview-trip">{{ $trip?->destination ?: 'Not selected yet' }}</span></x-detail-row>
                        <x-detail-row label="Initial status"><span class="text-emerald-700">Confirmed</span></x-detail-row>
                        <x-detail-row label="Reference">Generated on save</x-detail-row>
                    </dl>
                </x-summary-panel>

                <x-summary-panel title="What happens next">
                    <ol class="space-y-3">
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-[10px] font-semibold">1</span>
                            <div><p class="text-sm font-medium text-slate-900">Booking confirmed</p><p class="text-xs text-slate-500">Reference is generated</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">2</span>
                            <div><p class="text-sm font-medium text-slate-900">Services added</p><p class="text-xs text-slate-500">Add hotel, transport and activities</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">3</span>
                            <div><p class="text-sm font-medium text-slate-900">Customer payments recorded</p><p class="text-xs text-slate-500">Track deposits and balances</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">4</span>
                            <div><p class="text-sm font-medium text-slate-900">Invoice issued</p><p class="text-xs text-slate-500">When ready to bill the customer</p></div>
                        </li>
                    </ol>
                </x-summary-panel>
            </div>
        </div>

        {{-- Mobile sticky action bar --}}
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <x-button tag="a" href="{{ route('bookings.index') }}" variant="secondary" class="flex-1 justify-center">Cancel</x-button>
            <x-button type="submit" class="flex-1 justify-center">Create booking</x-button>
        </div>
    </form>
@endsection

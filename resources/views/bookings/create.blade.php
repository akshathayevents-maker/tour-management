@extends('layouts.app')

@section('title', 'New booking')

@php
    $customerOptions = \App\Models\Customer::orderBy('name')->get()->map(fn ($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'phone' => $c->phone,
        'email' => $c->email,
        'location' => collect([$c->city, $c->country])->filter()->join(', '),
        'since' => $c->created_at->format('d M Y'),
        'initials' => strtoupper(collect(explode(' ', trim($c->name)))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('')),
    ]);

    $tripOptions = \App\Models\Trip::with(['customer', 'itinerary'])->orderByDesc('id')->get()->map(fn ($t) => [
        'id' => $t->id,
        'destination' => $t->destination,
        'customerName' => $t->customer->name,
        'startDate' => $t->start_date?->format('d M Y'),
        'endDate' => $t->end_date?->format('d M Y'),
        'duration' => ($t->start_date && $t->end_date) ? $t->start_date->diffInDays($t->end_date) + 1 : null,
        'travellers' => $t->travellers_count,
        'status' => $t->status->label(),
        'statusColor' => $t->status->badgeColor(),
        'hasItinerary' => $t->itinerary !== null,
    ]);
@endphp

@section('content')
    <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-3">
        <a href="{{ route('bookings.index') }}" class="hover:text-slate-600">Bookings</a>
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        <span class="text-slate-500">New booking</span>
    </nav>

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900">Create a new booking</h1>
                <span class="inline-flex items-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20 px-2.5 py-0.5 text-[11px] font-medium">Direct booking</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Connect a traveller with their journey — confirmed instantly, no quotation required.</p>
        </div>
    </div>

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

    <form method="POST" action="{{ route('bookings.store') }}" class="pb-28 lg:pb-0" id="booking-form"
          onsubmit="this.querySelectorAll('button[type=submit]').forEach(b => { b.disabled = true; b.textContent = 'Creating booking…'; });">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-x-10 gap-y-8">
            {{-- Main workspace --}}
            <div class="lg:col-span-3 space-y-7">
                {{-- Step 1 — Customer --}}
                <div>
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white text-[11px] font-semibold">1</span>
                        <h3 class="text-sm font-semibold text-slate-900">Select traveller</h3>
                        <div class="flex-1 border-t border-dashed border-slate-200"></div>
                    </div>

                    @if ($customer)
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-3.5">
                            <div class="flex items-start gap-3">
                                <x-avatar :name="$customer->name" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $customer->name }}</p>
                                    <p class="text-xs text-slate-500 truncate mt-0.5">{{ $customer->phone }}@if ($customer->email) &middot; {{ $customer->email }} @endif</p>
                                    @if ($customer->city || $customer->country)
                                        <p class="text-xs text-slate-400 truncate">{{ collect([$customer->city, $customer->country])->filter()->join(', ') }}</p>
                                    @endif
                                </div>
                                <svg class="h-5 w-5 text-emerald-600 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-2 pt-2 border-t border-emerald-100/70">Customer since {{ $customer->created_at->format('d M Y') }}</p>
                        </div>
                        <input type="hidden" name="customer_id" id="customer_id" value="{{ $customer->id }}">
                    @else
                        <div id="customer-search-step">
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                                <input type="text" id="customer_search" autocomplete="off" required
                                       placeholder="Search by name or phone…"
                                       class="form-input pl-10 py-3 text-[15px] @error('customer_id') border-red-400 @enderror">
                            </div>
                            <div id="customer_results" class="hidden mt-1.5 rounded-xl border border-slate-200 bg-white shadow-lg max-h-72 overflow-y-auto divide-y divide-slate-50"></div>
                            @error('customer_id')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-slate-400">
                                Don't see them? <a href="{{ route('customers.create') }}" class="text-brand-700 hover:underline">Add a new customer</a>.
                            </p>
                        </div>

                        <div id="customer-selected-step" class="hidden rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-3.5">
                            <div class="flex items-start gap-3">
                                <x-avatar name="?" id="customer_sel_avatar" />
                                <div class="min-w-0 flex-1">
                                    <p id="customer_sel_name" class="text-sm font-semibold text-slate-900 truncate"></p>
                                    <p id="customer_sel_contact" class="text-xs text-slate-500 truncate mt-0.5"></p>
                                    <p id="customer_sel_location" class="text-xs text-slate-400 truncate"></p>
                                </div>
                                <svg class="h-5 w-5 text-emerald-600 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-emerald-100/70">
                                <p id="customer_sel_since" class="text-[11px] text-slate-400"></p>
                                <button type="button" id="customer_change_btn" class="shrink-0 text-xs font-medium text-brand-700 hover:underline">Change customer</button>
                            </div>
                        </div>
                        <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">
                    @endif
                </div>

                {{-- Step 2 — Trip --}}
                <div>
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white text-[11px] font-semibold">2</span>
                        <h3 class="text-sm font-semibold text-slate-900">Select journey</h3>
                        <div class="flex-1 border-t border-dashed border-slate-200"></div>
                    </div>

                    @if ($trip)
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $trip->destination }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        @if ($trip->start_date)
                                            {{ $trip->start_date->format('d M Y') }}@if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} &middot; {{ $trip->start_date->diffInDays($trip->end_date) + 1 }} days @endif
                                        @endif
                                        @if ($trip->travellers_count) &middot; {{ $trip->travellers_count }} {{ Str::plural('traveller', $trip->travellers_count) }} @endif
                                    </p>
                                </div>
                                <x-status-badge :color="$trip->status->badgeColor()" :label="$trip->status->label()" />
                            </div>
                        </div>
                        <input type="hidden" name="trip_id" id="trip_id" value="{{ $trip->id }}">
                    @elseif (\App\Models\Trip::count() === 0)
                        <x-empty-state
                            title="No trips yet"
                            description="Create a trip first, then come back to book it."
                            actionLabel="Create a trip"
                            :actionUrl="route('trips.create')" />
                        <input type="hidden" name="trip_id" id="trip_id" value="">
                    @else
                        <div id="trip-search-step">
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                                <input type="text" id="trip_search" autocomplete="off" required
                                       placeholder="Search by destination or traveller…"
                                       class="form-input pl-10 py-3 text-[15px] @error('trip_id') border-red-400 @enderror">
                            </div>
                            <div id="trip_results" class="hidden mt-1.5 rounded-xl border border-slate-200 bg-white shadow-lg max-h-72 overflow-y-auto divide-y divide-slate-50"></div>
                            @error('trip_id')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-slate-400">
                                Don't see it? <a href="{{ route('trips.create') }}" class="text-brand-700 hover:underline">Create a trip first</a>.
                            </p>
                        </div>

                        <div id="trip-selected-step" class="hidden rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p id="trip_sel_destination" class="text-sm font-semibold text-slate-900 truncate"></p>
                                    <p id="trip_sel_meta" class="text-xs text-slate-500 mt-0.5"></p>
                                    <p id="trip_sel_itinerary" class="hidden text-xs text-emerald-700 font-medium mt-0.5">Itinerary ready</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span id="trip_sel_status" class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"></span>
                                    <button type="button" id="trip_change_btn" class="text-xs font-medium text-slate-500 hover:text-slate-900">Change</button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="trip_id" id="trip_id" value="{{ old('trip_id') }}">
                    @endif
                </div>

                {{-- Step 3 — Confirmation behaviour --}}
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white text-[11px] font-semibold">3</span>
                    <p class="text-sm text-slate-600 flex-1">
                        Created instantly as <span class="font-semibold text-slate-900">Confirmed</span> — no quotation or approval step.
                    </p>
                    <span class="shrink-0 text-xs text-slate-400">Reference auto-generated</span>
                </div>

                {{-- Desktop actions --}}
                <div class="hidden lg:flex items-center justify-end gap-2">
                    <x-button tag="a" href="{{ route('bookings.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" id="submit-btn" disabled>Create booking</x-button>
                </div>
            </div>

            {{-- Booking summary --}}
            <div class="lg:col-span-2">
                <div class="lg:sticky lg:top-20 rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Booking summary</h3>
                    </div>

                    <div id="summary-empty" class="{{ $customer && $trip ? 'hidden' : '' }} px-5 py-8 text-center">
                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-700">Nothing to preview yet</p>
                        <p class="mt-1 text-xs text-slate-400 leading-relaxed">Pick a traveller and a journey on the left — their details, travel dates and booking status will appear here.</p>
                    </div>

                    <div id="summary-filled" class="{{ $customer && $trip ? '' : 'hidden' }}">
                        <div class="px-4 py-3.5 border-b border-slate-100 flex items-center gap-3">
                            <x-avatar name="?" id="summary_customer_avatar" size="sm" />
                            <div class="min-w-0">
                                <p id="summary_customer_name" class="text-sm font-semibold text-slate-900 truncate">{{ $customer?->name }}</p>
                                <p id="summary_customer_phone" class="text-xs text-slate-400 truncate">{{ $customer?->phone }}</p>
                            </div>
                        </div>
                        <div class="px-4 py-3.5 border-b border-slate-100">
                            <p id="summary_trip_destination" class="text-sm font-semibold text-slate-900">{{ $trip?->destination }}</p>
                            <p id="summary_trip_dates" class="text-xs text-slate-500 mt-0.5">
                                @if ($trip?->start_date){{ $trip->start_date->format('d M Y') }}@if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} &middot; {{ $trip->start_date->diffInDays($trip->end_date) + 1 }} days @endif @endif
                            </p>
                        </div>
                        <dl class="px-4 py-3.5 space-y-2.5 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-400 text-xs">Booking status</dt>
                                <dd><span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5 text-xs font-medium">Will be Confirmed</span></dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-400 text-xs">Booking reference</dt>
                                <dd class="text-xs text-slate-500">Generated on save</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/40">
                        <p class="text-[11px] text-slate-400 leading-relaxed">Once confirmed, you can add services, record payments and issue an invoice from the booking page.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile sticky action bar --}}
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <x-button tag="a" href="{{ route('bookings.index') }}" variant="secondary" class="flex-1 justify-center">Cancel</x-button>
            <x-button type="submit" id="submit-btn-mobile" disabled class="flex-1 justify-center">Create booking</x-button>
        </div>
    </form>

    <script>
        window.__customerOptions = @json($customerOptions);
        window.__tripOptions = @json($tripOptions);

        (function () {
            const customers = window.__customerOptions || [];
            const trips = window.__tripOptions || [];

            const customerIdField = document.getElementById('customer_id');
            const tripIdField = document.getElementById('trip_id');

            const summaryEmpty = document.getElementById('summary-empty');
            const summaryFilled = document.getElementById('summary-filled');

            function initials(name) {
                return name.trim().split(' ').map((p) => p[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
            }

            function escapeHtml(str) {
                return String(str).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            }

            // Wraps the matched portion of `text` in a <mark> so the dropdown
            // shows *why* a row matched the typed search term.
            function highlight(text, term) {
                const safe = escapeHtml(text);
                if (!term) return safe;
                const idx = text.toLowerCase().indexOf(term.toLowerCase());
                if (idx === -1) return safe;
                return escapeHtml(text.slice(0, idx))
                    + '<mark class="bg-amber-100 text-slate-900 rounded-sm px-0.5">' + escapeHtml(text.slice(idx, idx + term.length)) + '</mark>'
                    + escapeHtml(text.slice(idx + term.length));
            }

            function refreshSummary() {
                const customer = customers.find((c) => String(c.id) === String(customerIdField.value));
                const trip = trips.find((t) => String(t.id) === String(tripIdField.value));
                const bothSelected = !!(customer && trip);

                if (summaryEmpty) summaryEmpty.classList.toggle('hidden', bothSelected);
                if (summaryFilled) summaryFilled.classList.toggle('hidden', !bothSelected);

                if (customer) {
                    const av = document.getElementById('summary_customer_avatar');
                    if (av) av.textContent = initials(customer.name);
                    document.getElementById('summary_customer_name').textContent = customer.name;
                    document.getElementById('summary_customer_phone').textContent = customer.phone;
                }
                if (trip) {
                    document.getElementById('summary_trip_destination').textContent = trip.destination;
                    let dates = '';
                    if (trip.startDate) {
                        dates = trip.startDate + (trip.endDate ? ' – ' + trip.endDate + ' · ' + trip.duration + ' days' : '');
                    }
                    document.getElementById('summary_trip_dates').textContent = dates;
                }

                [document.getElementById('submit-btn'), document.getElementById('submit-btn-mobile')]
                    .filter(Boolean)
                    .forEach((b) => { b.disabled = !bothSelected; });
            }

            function buildDropdown(container, items, term, renderRow, onPick) {
                container.innerHTML = '';
                if (items.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'px-4 py-6 text-center';
                    empty.innerHTML = `<svg class="mx-auto h-5 w-5 text-slate-300 mb-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"/></svg>
                        <p class="text-sm text-slate-500">No matches for "${escapeHtml(term)}"</p>`;
                    container.appendChild(empty);
                    container.classList.remove('hidden');
                    return;
                }
                items.slice(0, 8).forEach((item) => {
                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'w-full text-left px-4 py-3 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none transition-colors';
                    row.innerHTML = renderRow(item, term);
                    row.addEventListener('click', () => onPick(item));
                    container.appendChild(row);
                });
                container.classList.remove('hidden');
            }

            // --- Customer search ---
            const customerSearch = document.getElementById('customer_search');
            if (customerSearch) {
                const results = document.getElementById('customer_results');
                const searchStep = document.getElementById('customer-search-step');
                const selectedStep = document.getElementById('customer-selected-step');

                function renderCustomerRow(c, term) {
                    const meta = [c.phone, c.location].filter(Boolean).join(' · ');
                    return `<div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-xs font-semibold">${initials(c.name)}</span>
                        <div class="min-w-0"><p class="text-sm font-medium text-slate-900 truncate">${highlight(c.name, term)}</p><p class="text-xs text-slate-400 truncate">${highlight(meta, term)}</p></div>
                    </div>`;
                }

                function selectCustomer(c) {
                    customerIdField.value = c.id;
                    document.getElementById('customer_sel_avatar').textContent = initials(c.name);
                    document.getElementById('customer_sel_name').textContent = c.name;
                    document.getElementById('customer_sel_contact').textContent = [c.phone, c.email].filter(Boolean).join(' · ');
                    document.getElementById('customer_sel_location').textContent = c.location || '';
                    document.getElementById('customer_sel_since').textContent = c.since ? 'Customer since ' + c.since : '';
                    searchStep.classList.add('hidden');
                    selectedStep.classList.remove('hidden');
                    results.classList.add('hidden');
                    refreshSummary();
                }

                customerSearch.addEventListener('input', () => {
                    const rawTerm = customerSearch.value.trim();
                    const term = rawTerm.toLowerCase();
                    if (!term) { results.classList.add('hidden'); customerIdField.value = ''; refreshSummary(); return; }
                    const matches = customers.filter((c) => c.name.toLowerCase().includes(term) || c.phone.includes(term));
                    buildDropdown(results, matches, rawTerm, renderCustomerRow, selectCustomer);
                });
                customerSearch.addEventListener('focus', () => {
                    if (customerSearch.value.trim()) customerSearch.dispatchEvent(new Event('input'));
                });
                document.addEventListener('click', (e) => {
                    if (!results.contains(e.target) && e.target !== customerSearch) results.classList.add('hidden');
                });

                document.getElementById('customer_change_btn')?.addEventListener('click', () => {
                    customerIdField.value = '';
                    customerSearch.value = '';
                    selectedStep.classList.add('hidden');
                    searchStep.classList.remove('hidden');
                    customerSearch.focus();
                    refreshSummary();
                });
            }

            // --- Trip search ---
            const tripSearch = document.getElementById('trip_search');
            if (tripSearch) {
                const results = document.getElementById('trip_results');
                const searchStep = document.getElementById('trip-search-step');
                const selectedStep = document.getElementById('trip-selected-step');
                const statusColors = { amber: 'bg-amber-50 text-amber-700', blue: 'bg-blue-50 text-blue-700', green: 'bg-emerald-50 text-emerald-700', slate: 'bg-slate-100 text-slate-500' };

                function renderTripRow(t, term) {
                    const dates = t.startDate ? t.startDate + (t.endDate ? ' – ' + t.endDate + ' · ' + t.duration + ' days' : '') : 'No dates set';
                    return `<div class="flex items-start justify-between gap-2">
                        <div class="min-w-0"><p class="text-sm font-medium text-slate-900 truncate">${highlight(t.destination, term)}</p><p class="text-xs text-slate-400 truncate">${dates} · ${highlight(t.customerName, term)}</p></div>
                        <span class="shrink-0 text-[11px] font-medium px-1.5 py-0.5 rounded-full ${statusColors[t.statusColor] || statusColors.slate}">${t.status}</span>
                    </div>`;
                }

                function selectTrip(t) {
                    tripIdField.value = t.id;
                    document.getElementById('trip_sel_destination').textContent = t.destination;
                    let meta = t.startDate ? (t.startDate + (t.endDate ? ' – ' + t.endDate + ' · ' + t.duration + ' days' : '')) : 'No dates set';
                    if (t.travellers) meta += ' · ' + t.travellers + ' travellers';
                    meta += ' · ' + t.customerName;
                    document.getElementById('trip_sel_meta').textContent = meta;
                    const itin = document.getElementById('trip_sel_itinerary');
                    itin.classList.toggle('hidden', !t.hasItinerary);
                    const statusEl = document.getElementById('trip_sel_status');
                    statusEl.textContent = t.status;
                    statusEl.className = 'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ' + (statusColors[t.statusColor] || statusColors.slate);
                    searchStep.classList.add('hidden');
                    selectedStep.classList.remove('hidden');
                    results.classList.add('hidden');
                    refreshSummary();
                }

                tripSearch.addEventListener('input', () => {
                    const rawTerm = tripSearch.value.trim();
                    const term = rawTerm.toLowerCase();
                    if (!term) { results.classList.add('hidden'); tripIdField.value = ''; refreshSummary(); return; }
                    const matches = trips.filter((t) => t.destination.toLowerCase().includes(term) || t.customerName.toLowerCase().includes(term));
                    buildDropdown(results, matches, rawTerm, renderTripRow, selectTrip);
                });
                tripSearch.addEventListener('focus', () => {
                    if (tripSearch.value.trim()) tripSearch.dispatchEvent(new Event('input'));
                });
                document.addEventListener('click', (e) => {
                    if (!results.contains(e.target) && e.target !== tripSearch) results.classList.add('hidden');
                });

                document.getElementById('trip_change_btn')?.addEventListener('click', () => {
                    tripIdField.value = '';
                    tripSearch.value = '';
                    selectedStep.classList.add('hidden');
                    searchStep.classList.remove('hidden');
                    tripSearch.focus();
                    refreshSummary();
                });
            }

            refreshSummary();
        })();
    </script>
@endsection

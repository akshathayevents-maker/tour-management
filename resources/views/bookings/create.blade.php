@extends('layouts.app')

@section('title', 'New booking')

@section('content')
    <a href="{{ route('bookings.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-700 mb-3">
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
        Bookings
    </a>

    <div class="mb-5">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-500 mb-1">New booking</p>
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Confirm a new booking</h2>
        <p class="mt-0.5 text-sm text-slate-500 max-w-lg">For a repeat customer, phone or WhatsApp booking — no quotation required.</p>
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

    {{-- Mobile compact summary --}}
    <div class="md:hidden mb-5 bg-white rounded-xl border border-slate-200/80 px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Summary</p>
            <p id="mobile-summary-text" class="text-sm text-slate-700 truncate">Fill in the form to build a summary</p>
        </div>
        <span class="shrink-0 inline-flex items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 px-2 py-0.5 text-[11px] font-medium">New booking</span>
    </div>

    <form method="POST" action="{{ route('bookings.store') }}" class="pb-24 md:pb-0" id="booking-form"
          oninput="const cSel = document.getElementById('customer_id'); const tSel = document.getElementById('trip_id');
                   const custName = (cSel ? cSel.options[cSel.selectedIndex]?.text.split(' · ')[0] : null) || '{{ $customer?->name }}' || 'Not selected';
                   const tripName = (tSel ? tSel.options[tSel.selectedIndex]?.text.split(' · ')[0] : null) || '{{ $trip?->destination }}' || 'Not selected';
                   document.getElementById('preview-customer').textContent = custName;
                   document.getElementById('preview-trip').textContent = tripName;
                   document.getElementById('mobile-summary-text').textContent = [custName !== 'Not selected' ? custName : null, tripName !== 'Not selected' ? tripName : null].filter(Boolean).join(' · ') || 'Fill in the form to build a summary';">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-8">
            {{-- Main form --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 px-5 sm:px-7">
                {{-- Customer --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">01</span>
                        <h3 class="text-sm font-semibold text-slate-900">Customer</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Who is this booking for?</p>

                    @if ($customer)
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                        <div class="flex items-center gap-2.5">
                            <x-avatar :name="$customer->name" size="sm" />
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $customer->name }}</p>
                                <p class="text-xs text-slate-400">{{ $customer->phone }}</p>
                            </div>
                        </div>
                    @else
                        <label for="customer_id" class="form-label">Customer <span class="form-required">*</span></label>
                        <select id="customer_id" name="customer_id" required class="form-select">
                            <option value="">Select a customer</option>
                            @foreach (\App\Models\Customer::orderBy('name')->get() as $option)
                                <option value="{{ $option->id }}" @selected(old('customer_id') == $option->id)>
                                    {{ $option->name }} &middot; {{ $option->phone }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')<p class="form-error">{{ $message }}</p>@enderror
                    @endif
                </section>

                {{-- Trip --}}
                <section class="py-5 sm:py-6">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">02</span>
                        <h3 class="text-sm font-semibold text-slate-900">Trip</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Which journey is being booked?</p>

                    @if ($trip)
                        <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                        <p class="text-sm font-medium text-slate-900">{{ $trip->destination }}</p>
                        @if ($trip->start_date)
                            <p class="text-xs text-slate-400">{{ $trip->start_date->format('d M Y') }}@if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} @endif</p>
                        @endif
                    @else
                        <label for="trip_id" class="form-label">Trip <span class="form-required">*</span></label>
                        <select id="trip_id" name="trip_id" required class="form-select">
                            <option value="">Select a trip</option>
                            @foreach (\App\Models\Trip::orderByDesc('id')->get() as $option)
                                <option value="{{ $option->id }}" @selected(old('trip_id') == $option->id)>
                                    {{ $option->destination }} &middot; {{ $option->customer->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="form-help">Don't see it? Create a trip first.</p>
                        @error('trip_id')<p class="form-error">{{ $message }}</p>@enderror
                    @endif
                </section>

                {{-- Desktop actions --}}
                <div class="hidden md:flex items-center justify-end gap-2 py-5 border-t border-slate-100">
                    <x-button tag="a" href="{{ route('bookings.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit">Confirm booking</x-button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="hidden lg:block lg:sticky lg:top-20 lg:self-start space-y-4">
                <x-context-card title="Booking preview">
                    <div class="text-center pb-4">
                        <div class="flex items-center justify-center gap-2 text-slate-300 mb-3">
                            <span class="h-px w-6 bg-slate-200"></span>
                            <svg class="h-4 w-4 text-accent-500" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            <span class="h-px w-6 bg-slate-200"></span>
                        </div>
                        <p class="text-sm font-semibold text-slate-900">New booking</p>
                    </div>
                    <dl class="space-y-3 text-sm border-t border-slate-50 pt-4">
                        <div>
                            <dt class="text-xs text-slate-400">Customer</dt>
                            <dd id="preview-customer" class="font-medium text-slate-900">{{ $customer?->name ?: 'Not selected' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Trip</dt>
                            <dd id="preview-trip" class="font-medium text-slate-900">{{ $trip?->destination ?: 'Not selected' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Status</dt>
                            <dd class="font-medium text-slate-900">Will be confirmed</dd>
                        </div>
                    </dl>
                </x-context-card>

                <x-context-card title="What happens next">
                    <ol class="space-y-3">
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-[10px] font-semibold">01</span>
                            <div><p class="text-sm font-medium text-slate-900">Booking confirmed</p><p class="text-xs text-slate-500">Reference is created</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">02</span>
                            <div><p class="text-sm font-medium text-slate-900">Services</p><p class="text-xs text-slate-500">Add hotel, transport and activities</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">03</span>
                            <div><p class="text-sm font-medium text-slate-900">Payments</p><p class="text-xs text-slate-500">Record customer payments</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">04</span>
                            <div><p class="text-sm font-medium text-slate-900">Invoice</p><p class="text-xs text-slate-500">Bill the customer when ready</p></div>
                        </li>
                    </ol>
                </x-context-card>
            </div>
        </div>

        {{-- Mobile sticky action bar --}}
        <div class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <x-button tag="a" href="{{ route('bookings.index') }}" variant="secondary" class="flex-1 justify-center">Cancel</x-button>
            <x-button type="submit" class="flex-1 justify-center">Confirm booking</x-button>
        </div>
    </form>
@endsection

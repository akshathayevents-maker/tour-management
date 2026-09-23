@extends('layouts.app')

@section('title', 'New booking')

@section('content')
    <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-3">
        <a href="{{ route('bookings.index') }}" class="hover:text-slate-700">Bookings</a>
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        <span class="text-slate-600">New booking</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Confirm a new booking</h2>
            <p class="mt-0.5 text-sm text-slate-500">For a repeat customer, phone or WhatsApp booking — no quotation required.</p>
        </div>
        <x-button tag="a" href="{{ route('bookings.index') }}" variant="secondary" size="sm">Back to bookings</x-button>
    </div>

    @if ($customer || $trip)
        <div class="mb-6 flex flex-wrap items-center gap-2 rounded-lg bg-brand-50/60 border border-brand-100 px-4 py-2.5 text-sm">
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

    <form method="POST" action="{{ route('bookings.store') }}"
          oninput="const cSel = document.getElementById('customer_id'); const tSel = document.getElementById('trip_id');
                   if (cSel) document.getElementById('preview-customer').textContent = cSel.options[cSel.selectedIndex]?.text.split(' · ')[0] || 'Not selected';
                   if (tSel) document.getElementById('preview-trip').textContent = tSel.options[tSel.selectedIndex]?.text.split(' · ')[0] || 'Not selected';">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-0">
                <x-form-section number="01" title="Customer" description="Who is this booking for?">
                    @if ($customer)
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                        <x-field label="Customer" name="customer_id" wide>
                            <div class="flex items-center gap-2.5 py-1">
                                <x-avatar :name="$customer->name" size="sm" />
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $customer->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $customer->phone }}</p>
                                </div>
                            </div>
                        </x-field>
                    @else
                        <x-field label="Customer" name="customer_id" required wide>
                            <select id="customer_id" name="customer_id" required class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                                <option value="">Select a customer</option>
                                @foreach (\App\Models\Customer::orderBy('name')->get() as $option)
                                    <option value="{{ $option->id }}" @selected(old('customer_id') == $option->id)>
                                        {{ $option->name }} &middot; {{ $option->phone }}
                                    </option>
                                @endforeach
                            </select>
                        </x-field>
                    @endif
                </x-form-section>

                <x-form-section number="02" title="Trip" description="Which journey is being booked?">
                    @if ($trip)
                        <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                        <x-field label="Trip" name="trip_id" wide>
                            <div class="py-1">
                                <p class="text-sm font-medium text-slate-900">{{ $trip->destination }}</p>
                                @if ($trip->start_date)
                                    <p class="text-xs text-slate-400">{{ $trip->start_date->format('d M Y') }}@if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} @endif</p>
                                @endif
                            </div>
                        </x-field>
                    @else
                        <x-field label="Trip" name="trip_id" required wide hint="Don't see it? Create a trip first.">
                            <select id="trip_id" name="trip_id" required class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                                <option value="">Select a trip</option>
                                @foreach (\App\Models\Trip::orderByDesc('id')->get() as $option)
                                    <option value="{{ $option->id }}" @selected(old('trip_id') == $option->id)>
                                        {{ $option->destination }} &middot; {{ $option->customer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </x-field>
                    @endif
                </x-form-section>

                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-5 pb-2">
                    <p class="text-xs text-slate-400">Services, payments and checklist are added after the booking is created.</p>
                    <div class="flex items-center gap-2 shrink-0 justify-end">
                        <x-button tag="a" href="{{ route('bookings.index') }}" variant="secondary">Cancel</x-button>
                        <x-button type="submit">Confirm booking</x-button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
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
    </form>
@endsection

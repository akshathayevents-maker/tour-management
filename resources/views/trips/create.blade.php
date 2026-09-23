@extends('layouts.app')

@section('title', 'New trip')

@section('content')
    <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-3">
        <a href="{{ route('trips.index') }}" class="hover:text-slate-700">Trips</a>
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        <span class="text-slate-600">New journey</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Plan a new journey</h2>
            <p class="mt-0.5 text-sm text-slate-500">Set up the traveller, destination and dates. You can build the itinerary and booking after the trip is created.</p>
        </div>
        <x-button tag="a" href="{{ route('trips.index') }}" variant="secondary" size="sm">Back to trips</x-button>
    </div>

    @if ($customer || $enquiry)
        <div class="mb-6 flex flex-wrap items-center gap-2 rounded-lg bg-brand-50/60 border border-brand-100 px-4 py-2.5 text-sm">
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">Connected to</span>
            @if ($customer)
                <span class="text-slate-700">{{ $customer->name }}</span>
            @endif
            @if ($enquiry)
                <span class="text-slate-400">&middot;</span>
                <a href="{{ route('enquiries.show', $enquiry) }}" class="text-brand-700 hover:underline">{{ $enquiry->destination }} enquiry</a>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('trips.store') }}"
          oninput="const cSel = document.getElementById('customer_id');
                   document.getElementById('preview-customer').textContent = (cSel ? cSel.options[cSel.selectedIndex]?.text.split(' · ')[0] : null) || '{{ $customer?->name }}' || 'Not selected';
                   document.getElementById('preview-dest').textContent = document.getElementById('destination').value.trim() || 'Not specified';
                   document.getElementById('preview-title').textContent = document.getElementById('destination').value.trim() || 'New journey';
                   const s = document.getElementById('start_date').value, e = document.getElementById('end_date').value;
                   document.getElementById('preview-dates').textContent = (s || e) ? [s, e].filter(Boolean).join(' – ') : 'Not specified';
                   if (s && e) {
                       const nights = Math.round((new Date(e) - new Date(s)) / 86400000);
                       document.getElementById('preview-duration').textContent = nights > 0 ? (nights + ' night' + (nights > 1 ? 's' : '') + ' · ' + (nights + 1) + ' days') : '';
                   } else { document.getElementById('preview-duration').textContent = ''; }
                   const t = document.getElementById('travellers_count').value;
                   document.getElementById('preview-travellers').textContent = t || '—';">
        @csrf

        @if ($enquiry)
            <input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-0">
                <x-form-section number="01" title="Traveller" description="Who is this journey for?">
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
                        <x-field label="Customer" name="customer_id" required wide hint="Don't see them? Add a customer first.">
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

                <x-form-section number="02" title="Journey" description="Where is this holiday going?">
                    <x-field label="Destination" name="destination" required wide>
                        <input id="destination" name="destination" value="{{ old('destination', $enquiry->destination ?? '') }}" required autofocus
                               placeholder="e.g. Goa Family Escape"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-[15px] py-2.5 px-3.5">
                    </x-field>
                </x-form-section>

                <x-form-section number="03" title="Travel dates" description="Set the beginning and end of the journey.">
                    <x-field label="Departure" name="start_date">
                        <input id="start_date" type="date" name="start_date"
                               value="{{ old('start_date', optional($enquiry->start_date ?? null)->format('Y-m-d')) }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Return" name="end_date">
                        <input id="end_date" type="date" name="end_date"
                               value="{{ old('end_date', optional($enquiry->end_date ?? null)->format('Y-m-d')) }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                </x-form-section>

                <x-form-section number="04" title="Travellers" description="How many people are travelling?">
                    <x-field label="Traveller count" name="travellers_count">
                        <input id="travellers_count" type="number" min="1" name="travellers_count"
                               value="{{ old('travellers_count', ($enquiry->adults ?? 0) + ($enquiry->children ?? 0) ?: '') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                </x-form-section>

                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-5 pb-2">
                    <p class="text-xs text-slate-400">Your trip details will be saved when you create the journey.</p>
                    <div class="flex items-center gap-2 shrink-0 justify-end">
                        <x-button tag="a" href="{{ route('trips.index') }}" variant="secondary">Cancel</x-button>
                        <x-button type="submit">Create trip</x-button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <x-context-card title="Trip preview">
                    <div class="text-center pb-4">
                        <div class="flex items-center justify-center gap-2 text-slate-300 mb-3">
                            <span class="h-px w-6 bg-slate-200"></span>
                            <svg class="h-4 w-4 text-accent-500" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
                            <span class="h-px w-6 bg-slate-200"></span>
                        </div>
                        <p id="preview-title" class="text-sm font-semibold text-slate-900">New journey</p>
                    </div>
                    <dl class="space-y-3 text-sm border-t border-slate-50 pt-4">
                        <div>
                            <dt class="text-xs text-slate-400">Traveller</dt>
                            <dd id="preview-customer" class="font-medium text-slate-900">{{ $customer?->name ?: 'Not selected' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Destination</dt>
                            <dd id="preview-dest" class="font-medium text-slate-900">{{ old('destination', $enquiry->destination ?? null) ?: 'Not specified' }}</dd>
                        </div>
                        @php
                            $previewStart = old('start_date', optional($enquiry->start_date ?? null)->format('Y-m-d'));
                            $previewEnd = old('end_date', optional($enquiry->end_date ?? null)->format('Y-m-d'));
                            $previewNights = ($previewStart && $previewEnd) ? \Carbon\Carbon::parse($previewStart)->diffInDays(\Carbon\Carbon::parse($previewEnd)) : null;
                        @endphp
                        <div>
                            <dt class="text-xs text-slate-400">Travel dates</dt>
                            <dd id="preview-dates" class="font-medium text-slate-900">{{ $previewStart || $previewEnd ? collect([$previewStart, $previewEnd])->filter()->join(' – ') : 'Not specified' }}</dd>
                            <dd id="preview-duration" class="text-xs text-slate-400 mt-0.5">{{ $previewNights > 0 ? "{$previewNights} night".($previewNights > 1 ? 's' : '').' · '.($previewNights + 1).' days' : '' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Travellers</dt>
                            <dd id="preview-travellers" class="font-medium text-slate-900">{{ old('travellers_count', ($enquiry->adults ?? 0) + ($enquiry->children ?? 0)) ?: '—' }}</dd>
                        </div>
                    </dl>
                </x-context-card>

                <x-context-card title="What happens next">
                    <ol class="space-y-3">
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-[10px] font-semibold">01</span>
                            <div><p class="text-sm font-medium text-slate-900">Trip created</p><p class="text-xs text-slate-500">Journey is set up</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">02</span>
                            <div><p class="text-sm font-medium text-slate-900">Itinerary</p><p class="text-xs text-slate-500">Build the day-by-day plan</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">03</span>
                            <div><p class="text-sm font-medium text-slate-900">Quotation</p><p class="text-xs text-slate-500">Price the journey</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">04</span>
                            <div><p class="text-sm font-medium text-slate-900">Booking</p><p class="text-xs text-slate-500">Confirm and manage payments</p></div>
                        </li>
                    </ol>
                </x-context-card>
            </div>
        </div>
    </form>
@endsection

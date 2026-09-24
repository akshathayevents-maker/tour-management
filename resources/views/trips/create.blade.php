@extends('layouts.app')

@section('title', 'New trip')

@section('content')
    <a href="{{ route('trips.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-700 mb-3">
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
        Trips
    </a>

    <div class="mb-5">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-500 mb-1">New journey</p>
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Plan a new journey</h2>
        <p class="mt-0.5 text-sm text-slate-500 max-w-lg">Set up the traveller, destination and dates. You can build the itinerary and booking after the trip is created.</p>
    </div>

    @if ($customer || $enquiry)
        <div class="mb-5 flex flex-wrap items-center gap-2 rounded-lg bg-brand-50/60 border border-brand-100 px-4 py-2.5 text-sm">
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">Connected to</span>
            @if ($customer)
                <span class="text-slate-700">{{ $customer->name }}</span>
            @endif
            @if ($enquiry)
                @if ($customer)<span class="text-slate-400">&middot;</span>@endif
                <a href="{{ route('enquiries.show', $enquiry) }}" class="text-brand-700 hover:underline">{{ $enquiry->destination }} enquiry</a>
            @endif
        </div>
    @endif

    {{-- Mobile compact summary --}}
    <div class="md:hidden mb-5 bg-white rounded-xl border border-slate-200/80 px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Summary</p>
            <p id="mobile-summary-text" class="text-sm text-slate-700 truncate">Fill in the form to build a summary</p>
        </div>
        <span class="shrink-0 inline-flex items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 px-2 py-0.5 text-[11px] font-medium">New trip</span>
    </div>

    <form method="POST" action="{{ route('trips.store') }}" class="pb-24 md:pb-0" id="trip-form"
          oninput="const cSel = document.getElementById('customer_id');
                   const custName = (cSel ? cSel.options[cSel.selectedIndex]?.text.split(' · ')[0] : null) || '{{ $customer?->name }}' || 'Not selected';
                   document.getElementById('preview-customer').textContent = custName;
                   const destVal = document.getElementById('destination').value.trim();
                   document.getElementById('preview-dest').textContent = destVal || 'Not specified';
                   document.getElementById('preview-title').textContent = destVal || 'New journey';
                   const s = document.getElementById('start_date').value, e = document.getElementById('end_date').value;
                   document.getElementById('preview-dates').textContent = (s || e) ? [s, e].filter(Boolean).join(' – ') : 'Not specified';
                   if (s && e) {
                       const nights = Math.round((new Date(e) - new Date(s)) / 86400000);
                       document.getElementById('preview-duration').textContent = nights > 0 ? (nights + ' night' + (nights > 1 ? 's' : '') + ' · ' + (nights + 1) + ' days') : '';
                   } else { document.getElementById('preview-duration').textContent = ''; }
                   const t = document.getElementById('travellers_count').value;
                   document.getElementById('preview-travellers').textContent = t || '—';
                   document.getElementById('mobile-summary-text').textContent = [destVal, custName !== 'Not selected' ? custName : null].filter(Boolean).join(' · ') || 'Fill in the form to build a summary';">
        @csrf

        @if ($enquiry)
            <input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-8">
            {{-- Main form --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 px-5 sm:px-7">
                {{-- Traveller --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">01</span>
                        <h3 class="text-sm font-semibold text-slate-900">Traveller</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Who is this journey for?</p>

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
                        <p class="form-help">Don't see them? Add a customer first.</p>
                        @error('customer_id')<p class="form-error">{{ $message }}</p>@enderror
                    @endif
                </section>

                {{-- Journey --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">02</span>
                        <h3 class="text-sm font-semibold text-slate-900">Journey</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Where is this holiday going?</p>

                    <label for="destination" class="form-label">Destination <span class="form-required">*</span></label>
                    <input id="destination" name="destination" value="{{ old('destination', $enquiry->destination ?? '') }}" required autofocus
                           placeholder="e.g. Goa Family Escape"
                           class="form-input text-[15px] font-medium">
                    @error('destination')<p class="form-error">{{ $message }}</p>@enderror
                </section>

                {{-- Travel dates --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">03</span>
                        <h3 class="text-sm font-semibold text-slate-900">Travel dates</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Set the beginning and end of the journey.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="start_date" class="form-label">Departure</label>
                            <input id="start_date" type="date" name="start_date"
                                   value="{{ old('start_date', optional($enquiry->start_date ?? null)->format('Y-m-d')) }}"
                                   class="form-input">
                            @error('start_date')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="end_date" class="form-label">Return</label>
                            <input id="end_date" type="date" name="end_date"
                                   value="{{ old('end_date', optional($enquiry->end_date ?? null)->format('Y-m-d')) }}"
                                   class="form-input">
                            @error('end_date')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="travellers_count" class="form-label">Travellers</label>
                            <input id="travellers_count" type="number" inputmode="numeric" min="1" name="travellers_count"
                                   value="{{ old('travellers_count', ($enquiry->adults ?? 0) + ($enquiry->children ?? 0) ?: '') }}"
                                   placeholder="Number of travellers"
                                   class="form-input">
                            @error('travellers_count')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Desktop actions --}}
                <div class="hidden md:flex items-center justify-end gap-2 py-5 border-t border-slate-100">
                    <x-button tag="a" href="{{ route('trips.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit">Create trip</x-button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="hidden lg:block lg:sticky lg:top-20 lg:self-start space-y-4">
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

        {{-- Mobile sticky action bar --}}
        <div class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <x-button tag="a" href="{{ route('trips.index') }}" variant="secondary" class="flex-1 justify-center">Cancel</x-button>
            <x-button type="submit" class="flex-1 justify-center">Create trip</x-button>
        </div>
    </form>
@endsection

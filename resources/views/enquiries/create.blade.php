@extends('layouts.app')

@section('title', 'Add enquiry')

@section('content')
    <a href="{{ route('enquiries.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-700 mb-3">
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
        Enquiries
    </a>

    <div class="mb-5">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-500 mb-1">New holiday enquiry</p>
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Start a new holiday enquiry</h2>
        <p class="mt-0.5 text-sm text-slate-500 max-w-lg">Capture the trip requirements so you can prepare the right itinerary and follow up at the right time.</p>
    </div>

    @if ($customer)
        <div class="mb-5 flex flex-wrap items-center gap-2 rounded-lg bg-brand-50/60 border border-brand-100 px-4 py-2.5 text-sm">
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">Connected to</span>
            <span class="text-slate-700">{{ $customer->name }}</span>
        </div>
    @endif

    {{-- Mobile compact summary --}}
    <div class="md:hidden mb-5 bg-white rounded-xl border border-slate-200/80 px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Summary</p>
            <p id="mobile-summary-text" class="text-sm text-slate-700 truncate">Fill in the form to build a summary</p>
        </div>
        <span class="shrink-0 inline-flex items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 px-2 py-0.5 text-[11px] font-medium">New enquiry</span>
    </div>

    <form method="POST" action="{{ route('enquiries.store') }}" class="pb-24 md:pb-0" id="enquiry-form"
          oninput="const d = document.getElementById('destination'); const s = document.getElementById('start_date'); const e = document.getElementById('end_date');
                   const destVal = d ? d.value.trim() : '';
                   document.getElementById('preview-dest').textContent = destVal || 'Not specified';
                   const dates = [s && s.value ? s.value : null, e && e.value ? e.value : null].filter(Boolean).join(' – ');
                   document.getElementById('preview-dates').textContent = dates || 'Not specified';
                   const custSel = document.getElementById('customer_id');
                   const custName = custSel ? (custSel.options[custSel.selectedIndex]?.text.split(' · ')[0] || '') : '{{ $customer?->name }}';
                   document.getElementById('mobile-summary-text').textContent = [custName, destVal].filter(Boolean).join(' · ') || 'Fill in the form to build a summary';">
        @csrf

        @if ($customer)
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
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
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Who is enquiring about a holiday?</p>

                    @if ($customer)
                        <p class="text-sm text-slate-900">{{ $customer->name }} &middot; {{ $customer->phone }}</p>
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

                {{-- Holiday requirement --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">02</span>
                        <h3 class="text-sm font-semibold text-slate-900">Holiday requirement</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Where are they travelling, and when?</p>

                    <div class="space-y-4">
                        <div>
                            <label for="destination" class="form-label">Destination <span class="form-required">*</span></label>
                            <input id="destination" name="destination" value="{{ old('destination') }}" required autofocus
                                   placeholder="Where are they planning to go?"
                                   class="form-input text-[15px] font-medium">
                            @error('destination')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="start_date" class="form-label">Start date</label>
                                <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}"
                                       class="form-input">
                                @error('start_date')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="end_date" class="form-label">End date</label>
                                <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}"
                                       class="form-input">
                                @error('end_date')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="budget" class="form-label">Budget</label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">₹</span>
                                    <input id="budget" name="budget" type="number" inputmode="decimal" step="0.01" min="0" value="{{ old('budget') }}"
                                           placeholder="Approx. budget"
                                           class="form-input pl-7 font-medium">
                                </div>
                                @error('budget')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="adults" class="form-label">Adults</label>
                                <input id="adults" name="adults" type="number" inputmode="numeric" min="0" value="{{ old('adults') }}"
                                       class="form-input">
                                @error('adults')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="children" class="form-label">Children</label>
                                <input id="children" name="children" type="number" inputmode="numeric" min="0" value="{{ old('children') }}"
                                       class="form-input">
                                @error('children')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="hotel_category" class="form-label">Hotel category</label>
                                <input id="hotel_category" name="hotel_category" value="{{ old('hotel_category') }}"
                                       placeholder="e.g. 4-star"
                                       class="form-input">
                                @error('hotel_category')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                            <div>
                                <label for="meal_plan" class="form-label">Meal plan</label>
                                <input id="meal_plan" name="meal_plan" value="{{ old('meal_plan') }}"
                                       placeholder="e.g. Breakfast included"
                                       class="form-input">
                                @error('meal_plan')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <label class="flex items-center gap-2 text-sm text-slate-700 py-2.5">
                                <input type="checkbox" name="transport_required" value="1" class="rounded border-slate-300" @checked(old('transport_required'))>
                                Transport required
                            </label>
                        </div>
                    </div>
                </section>

                {{-- Planning notes --}}
                <section class="py-5 sm:py-6">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">03</span>
                        <h3 class="text-sm font-semibold text-slate-900">Planning notes</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Preferences or anything else worth remembering.</p>

                    <textarea id="notes" name="notes" rows="3" placeholder="Preferences, special occasions, previous conversation…"
                              class="form-textarea leading-relaxed" style="min-height: 100px">{{ old('notes') }}</textarea>
                    @error('notes')<p class="form-error">{{ $message }}</p>@enderror
                </section>

                {{-- Desktop actions --}}
                <div class="hidden md:flex items-center justify-end gap-2 py-5 border-t border-slate-100">
                    <x-button tag="a" href="{{ route('enquiries.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit">Save enquiry</x-button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="hidden lg:block lg:sticky lg:top-20 lg:self-start space-y-4">
                <x-context-card title="Trip brief">
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-slate-400">Traveller</dt>
                            <dd class="font-medium text-slate-900">{{ $customer?->name ?: 'Not selected' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Destination</dt>
                            <dd id="preview-dest" class="font-medium text-slate-900">{{ old('destination') ?: 'Not specified' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Travel dates</dt>
                            <dd id="preview-dates" class="font-medium text-slate-900">Not specified</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Status</dt>
                            <dd class="font-medium text-slate-900">New enquiry</dd>
                        </div>
                    </dl>
                </x-context-card>

                <x-context-card title="What happens next">
                    <ol class="space-y-3">
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-[10px] font-semibold">01</span>
                            <div><p class="text-sm font-medium text-slate-900">Enquiry logged</p><p class="text-xs text-slate-500">Requirement is captured</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">02</span>
                            <div><p class="text-sm font-medium text-slate-900">Trip</p><p class="text-xs text-slate-500">Start planning once ready</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">03</span>
                            <div><p class="text-sm font-medium text-slate-900">Booking</p><p class="text-xs text-slate-500">Confirm once the trip is ready</p></div>
                        </li>
                    </ol>
                </x-context-card>
            </div>
        </div>

        {{-- Mobile sticky action bar --}}
        <div class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <x-button tag="a" href="{{ route('enquiries.index') }}" variant="secondary" class="flex-1 justify-center">Cancel</x-button>
            <x-button type="submit" class="flex-1 justify-center">Save enquiry</x-button>
        </div>
    </form>
@endsection

@extends('layouts.app')

@section('title', 'Add lead')

@section('content')
    <a href="{{ route('leads.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-700 mb-3">
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
        Leads
    </a>

    <div class="mb-5">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-500 mb-1">New travel opportunity</p>
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Capture a lead</h2>
        <p class="mt-0.5 text-sm text-slate-500 max-w-lg">Record a traveller and their holiday requirements so your team can follow up and convert the opportunity.</p>
    </div>

    {{-- Mobile compact summary --}}
    <div class="md:hidden mb-5 bg-white rounded-xl border border-slate-200/80 px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Summary</p>
            <p id="mobile-summary-text" class="text-sm text-slate-700 truncate">Fill in the form to build a summary</p>
        </div>
        <span class="shrink-0 inline-flex items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 px-2 py-0.5 text-[11px] font-medium">New lead</span>
    </div>

    <form method="POST" action="{{ route('leads.store') }}" class="pb-24 md:pb-0" id="lead-form"
          oninput="const b = (id, val) => document.getElementById('brief-'+id).textContent = val || '—';
                   b('name', document.getElementById('name').value.trim());
                   b('dest', document.getElementById('destination').value.trim());
                   b('date', document.getElementById('travel_month').value);
                   b('travellers', document.getElementById('travellers_count').value ? document.getElementById('travellers_count').value + ' travellers' : '');
                   b('budget', document.getElementById('budget').value ? '₹' + Number(document.getElementById('budget').value).toLocaleString() : '');
                   document.getElementById('mobile-summary-text').textContent =
                       [document.getElementById('name').value.trim(), document.getElementById('destination').value.trim()].filter(Boolean).join(' · ') || 'Fill in the form to build a summary';">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-8">
            {{-- Main form --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 px-5 sm:px-7">
                {{-- Traveller --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">01</span>
                        <h3 class="text-sm font-semibold text-slate-900">Traveller</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Who are you planning for?</p>

                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
                        <div class="sm:col-span-3">
                            <label for="name" class="form-label">Full name <span class="form-required">*</span></label>
                            <input id="name" name="name" value="{{ old('name') }}" required autofocus
                                   autocomplete="name" placeholder="Enter traveller's full name"
                                   class="form-input">
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="phone" class="form-label">Phone <span class="form-required">*</span></label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" required
                                   type="tel" inputmode="tel" autocomplete="tel" placeholder="+91 XXXXX XXXXX"
                                   class="form-input">
                            @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Travel plans --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">02</span>
                        <h3 class="text-sm font-semibold text-slate-900">Travel plans</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Where are they dreaming of going? Optional — fill in what you know now.</p>

                    <div class="space-y-4">
                        <div>
                            <label for="destination" class="form-label">Destination</label>
                            <input id="destination" name="destination" value="{{ old('destination') }}"
                                   placeholder="Where are they planning to go?"
                                   class="form-input text-[15px] font-medium">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="travel_month" class="form-label">Travel date</label>
                                <input id="travel_month" name="travel_month" type="date" value="{{ old('travel_month') }}"
                                       class="form-input">
                            </div>
                            <div>
                                <label for="travellers_count" class="form-label">Travellers</label>
                                <input id="travellers_count" name="travellers_count" type="number" inputmode="numeric" min="1" value="{{ old('travellers_count') }}"
                                       placeholder="Number of travellers"
                                       class="form-input">
                            </div>
                            <div>
                                <label for="budget" class="form-label">Budget</label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">₹</span>
                                    <input id="budget" name="budget" type="number" inputmode="decimal" step="0.01" min="0" value="{{ old('budget') }}"
                                           placeholder="Approx. budget"
                                           class="form-input pl-7 font-medium">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- How they found you --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">03</span>
                        <h3 class="text-sm font-semibold text-slate-900">How they found you</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">How did they find you? <span class="form-required">*</span></p>

                    <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                        @foreach (\App\Enums\LeadSource::cases() as $source)
                            <label class="cursor-pointer">
                                <input type="radio" name="source" value="{{ $source->value }}" class="peer sr-only" required @checked(old('source') === $source->value)>
                                <span class="form-choice flex items-center justify-center gap-1.5 text-center peer-checked:bg-brand-700 peer-checked:border-brand-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-1 hover:border-brand-400">
                                    <svg class="h-3.5 w-3.5 shrink-0 hidden peer-checked:inline" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                    {{ $source->label() }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('source')<p class="form-error">{{ $message }}</p>@enderror
                </section>

                {{-- Notes --}}
                <section class="py-5 sm:py-6">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">04</span>
                        <h3 class="text-sm font-semibold text-slate-900">Conversation notes</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Anything important for the next conversation.</p>

                    <textarea id="notes" name="notes" rows="3" placeholder="Preferences, family details, hotel expectations, special occasions, previous conversation…"
                              class="form-textarea leading-relaxed" style="min-height: 100px">{{ old('notes') }}</textarea>
                </section>

                {{-- Desktop actions --}}
                <div class="hidden md:flex items-center justify-end gap-2 py-5 border-t border-slate-100">
                    <x-button tag="a" href="{{ route('leads.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit">Create lead</x-button>
                </div>
            </div>

            {{-- Opportunity brief — desktop only --}}
            <div class="hidden lg:block lg:sticky lg:top-20 lg:self-start">
                <div class="bg-white rounded-xl border border-slate-200/80 overflow-hidden text-sm">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Travel brief</p>
                            <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 px-2 py-0.5 text-[11px] font-medium">New lead</span>
                        </div>
                        <dl class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-xs text-slate-400 shrink-0">Traveller</dt>
                                <dd id="brief-name" class="font-medium text-slate-900 text-right truncate">{{ old('name') ?: '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-xs text-slate-400 shrink-0">Destination</dt>
                                <dd id="brief-dest" class="font-medium text-slate-900 text-right truncate">{{ old('destination') ?: '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-xs text-slate-400 shrink-0">Travel date</dt>
                                <dd id="brief-date" class="font-medium text-slate-900 text-right truncate">{{ old('travel_month') ?: '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-xs text-slate-400 shrink-0">Travellers</dt>
                                <dd id="brief-travellers" class="font-medium text-slate-900 text-right truncate">{{ old('travellers_count') ? old('travellers_count').' travellers' : '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-xs text-slate-400 shrink-0">Budget</dt>
                                <dd id="brief-budget" class="font-medium text-slate-900 text-right truncate">{{ old('budget') ? '₹'.number_format(old('budget')) : '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="border-t border-slate-100 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-3">Opportunity journey</p>
                        <ol class="relative">
                            @foreach (['Lead captured', 'Follow-up', 'Qualified', 'Quotation', 'Booking'] as $stage)
                                <li class="relative flex items-center gap-2.5 pb-3 last:pb-0">
                                    @unless ($loop->last)
                                        <span class="absolute left-[3px] top-3 bottom-0 w-px bg-slate-200"></span>
                                    @endunless
                                    <span class="relative z-10 h-2 w-2 rounded-full shrink-0 {{ $loop->first ? 'bg-brand-700' : 'border border-slate-300 bg-white' }}"></span>
                                    <span class="text-[13px] {{ $loop->first ? 'font-medium text-slate-900' : 'text-slate-400' }}">{{ $stage }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    <div class="border-t border-slate-100 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Next action</p>
                        <p class="text-[13px] text-slate-600 leading-relaxed">Save this lead to begin follow-up.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile sticky action bar --}}
        <div class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <x-button tag="a" href="{{ route('leads.index') }}" variant="secondary" class="flex-1 justify-center">Cancel</x-button>
            <x-button type="submit" class="flex-1 justify-center">Create lead</x-button>
        </div>
    </form>
@endsection

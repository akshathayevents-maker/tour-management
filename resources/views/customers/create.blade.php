@extends('layouts.app')

@section('title', 'Add customer')

@section('content')
    <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-700 mb-3">
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
        Customers
    </a>

    <div class="mb-5">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-500 mb-1">New guest profile</p>
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Create a guest profile</h2>
        <p class="mt-0.5 text-sm text-slate-500 max-w-lg">Start the relationship with the traveller and keep everything about their journey in one place.</p>
    </div>

    {{-- Mobile compact summary --}}
    <div class="md:hidden mb-5 bg-white rounded-xl border border-slate-200/80 px-4 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Summary</p>
            <p id="mobile-summary-text" class="text-sm text-slate-700 truncate">Fill in the form to build a summary</p>
        </div>
        <span class="shrink-0 inline-flex items-center rounded-full bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 px-2 py-0.5 text-[11px] font-medium">New guest</span>
    </div>

    <form method="POST" action="{{ route('customers.store') }}" class="pb-24 md:pb-0" id="customer-form"
          oninput="const nameVal = document.getElementById('name').value.trim();
                   document.getElementById('preview-name').textContent = nameVal || 'New guest';
                   document.getElementById('preview-initials').textContent = nameVal ? nameVal.split(' ').map(p => p[0]).slice(0,2).join('').toUpperCase() : '—';
                   const phoneVal = document.getElementById('phone').value.trim();
                   document.getElementById('preview-phone').textContent = phoneVal || 'No contact details yet';
                   document.getElementById('mobile-summary-text').textContent = [nameVal, phoneVal].filter(Boolean).join(' · ') || 'Fill in the form to build a summary';">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-8 gap-y-8">
            {{-- Main form --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 px-5 sm:px-7">
                {{-- Guest profile --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">01</span>
                        <h3 class="text-sm font-semibold text-slate-900">Guest profile</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Who are you planning this holiday for?</p>

                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
                        <div class="sm:col-span-3">
                            <label for="name" class="form-label">Full name <span class="form-required">*</span></label>
                            <input id="name" name="name" value="{{ old('name') }}" required autofocus
                                   autocomplete="name" placeholder="Enter guest's full name"
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
                    <div class="mt-4">
                        <label for="whatsapp" class="form-label">WhatsApp number</label>
                        <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                               placeholder="Optional — leave blank if same as phone"
                               class="form-input">
                        @error('whatsapp')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                {{-- Contact details --}}
                <section class="py-5 sm:py-6 border-b border-slate-100">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">02</span>
                        <h3 class="text-sm font-semibold text-slate-900">Contact details</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Optional details that help you communicate and plan their trip.</p>

                    <div class="space-y-4">
                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                   placeholder="name@example.com"
                                   class="form-input">
                            @error('email')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="form-label">City</label>
                                <input id="city" name="city" value="{{ old('city') }}"
                                       class="form-input">
                                @error('city')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="country" class="form-label">Country</label>
                                <input id="country" name="country" value="{{ old('country') }}"
                                       class="form-input">
                                @error('country')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Travel notes --}}
                <section class="py-5 sm:py-6">
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-xs font-semibold text-accent-500 tabular-nums">03</span>
                        <h3 class="text-sm font-semibold text-slate-900">Travel notes</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3 sm:mb-4">Anything worth remembering about this guest.</p>

                    <textarea id="notes" name="notes" rows="3" placeholder="Preferences, family details, past trips…"
                              class="form-textarea leading-relaxed" style="min-height: 100px">{{ old('notes') }}</textarea>
                    @error('notes')<p class="form-error">{{ $message }}</p>@enderror
                </section>

                {{-- Desktop actions --}}
                <div class="hidden md:flex items-center justify-end gap-2 py-5 border-t border-slate-100">
                    <x-button tag="a" href="{{ route('customers.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit">Create customer</x-button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="hidden lg:block lg:sticky lg:top-20 lg:self-start space-y-4">
                <x-context-card title="Guest preview">
                    <div class="text-center pb-4">
                        <span id="preview-initials" class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-600 text-lg font-semibold">{{ old('name') ? collect(explode(' ', trim(old('name'))))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('') : '—' }}</span>
                        <p id="preview-name" class="mt-3 text-sm font-semibold text-slate-900">{{ old('name') ?: 'New guest' }}</p>
                        <p id="preview-phone" class="text-xs text-slate-400">{{ old('phone') ?: 'No contact details yet' }}</p>
                    </div>
                </x-context-card>

                <x-context-card title="What happens next">
                    <ol class="space-y-3">
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-[10px] font-semibold">01</span>
                            <div><p class="text-sm font-medium text-slate-900">Guest profile</p><p class="text-xs text-slate-500">Customer information is saved</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">02</span>
                            <div><p class="text-sm font-medium text-slate-900">Enquiry</p><p class="text-xs text-slate-500">Start planning their holiday</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">03</span>
                            <div><p class="text-sm font-medium text-slate-900">Trip</p><p class="text-xs text-slate-500">Build the itinerary and travel plan</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">04</span>
                            <div><p class="text-sm font-medium text-slate-900">Booking</p><p class="text-xs text-slate-500">Convert the journey into a confirmed booking</p></div>
                        </li>
                    </ol>
                </x-context-card>
            </div>
        </div>

        {{-- Mobile sticky action bar --}}
        <div class="md:hidden fixed bottom-0 inset-x-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex items-center gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <x-button tag="a" href="{{ route('customers.index') }}" variant="secondary" class="flex-1 justify-center">Cancel</x-button>
            <x-button type="submit" class="flex-1 justify-center">Create customer</x-button>
        </div>
    </form>
@endsection

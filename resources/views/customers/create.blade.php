@extends('layouts.app')

@section('title', 'Add customer')

@section('content')
    <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-3">
        <a href="{{ route('customers.index') }}" class="hover:text-slate-700">Customers</a>
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        <span class="text-slate-600">New guest</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Create a guest profile</h2>
            <p class="mt-0.5 text-sm text-slate-500">Start the relationship with the traveller and keep everything about their journey in one place.</p>
        </div>
        <x-button tag="a" href="{{ route('customers.index') }}" variant="secondary" size="sm">Back to customers</x-button>
    </div>

    <form method="POST" action="{{ route('customers.store') }}" id="customer-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-0">
                <x-form-section number="01" title="Guest profile" description="Who are you planning this holiday for?">
                    <x-field label="Full name" name="name" required wide>
                        <input id="name" name="name" value="{{ old('name') }}" required autofocus
                               oninput="document.getElementById('preview-name').textContent = this.value.trim() || 'New guest'; document.getElementById('preview-initials').textContent = this.value.trim() ? this.value.trim().split(' ').map(p => p[0]).slice(0,2).join('').toUpperCase() : '—';"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-[15px] py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Phone" name="phone" required hint="Primary contact method.">
                        <input id="phone" name="phone" value="{{ old('phone') }}" required
                               oninput="document.getElementById('preview-phone').textContent = this.value.trim() || 'No contact details yet';"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="WhatsApp number" name="whatsapp" hint="Optional — leave blank if same as phone.">
                        <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                </x-form-section>

                <x-form-section number="02" title="Contact details" description="Optional details that help you communicate and plan their trip.">
                    <x-field label="Email" name="email">
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="City" name="city">
                        <input id="city" name="city" value="{{ old('city') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Country" name="country">
                        <input id="country" name="country" value="{{ old('country') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                </x-form-section>

                <x-form-section number="03" title="Travel notes" description="Anything worth remembering about this guest.">
                    <x-field label="Notes" name="notes" wide>
                        <textarea id="notes" name="notes" rows="4"
                                  class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">{{ old('notes') }}</textarea>
                    </x-field>
                </x-form-section>

                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-5 pb-2">
                    <p class="text-xs text-slate-400">Changes are saved only when you create the record.</p>
                    <div class="flex items-center gap-2 shrink-0 justify-end">
                        <x-button tag="a" href="{{ route('customers.index') }}" variant="secondary">Cancel</x-button>
                        <x-button type="submit">Create customer</x-button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
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
    </form>
@endsection

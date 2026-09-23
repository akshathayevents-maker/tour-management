@extends('layouts.app')

@section('title', 'Add enquiry')

@section('content')
    <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-3">
        <a href="{{ route('enquiries.index') }}" class="hover:text-slate-700">Enquiries</a>
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        <span class="text-slate-600">New enquiry</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Start a new holiday enquiry</h2>
            <p class="mt-0.5 text-sm text-slate-500">Capture the trip requirements so you can prepare the right itinerary and follow up at the right time.</p>
        </div>
        <x-button tag="a" href="{{ route('enquiries.index') }}" variant="secondary" size="sm">Back to enquiries</x-button>
    </div>

    <form method="POST" action="{{ route('enquiries.store') }}"
          oninput="const d = document.getElementById('destination'); const s = document.getElementById('start_date'); const e = document.getElementById('end_date');
                   document.getElementById('preview-dest').textContent = (d ? d.value.trim() : '') || 'Not specified';
                   const dates = [s && s.value ? s.value : null, e && e.value ? e.value : null].filter(Boolean).join(' – ');
                   document.getElementById('preview-dates').textContent = dates || 'Not specified';">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-0">
                <x-form-section number="01" title="Traveller" description="Who is enquiring about a holiday?">
                    @if ($customer)
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                        <x-field label="Customer" name="customer_id" wide>
                            <p class="text-sm text-slate-900 py-1.5">{{ $customer->name }} &middot; {{ $customer->phone }}</p>
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

                <x-form-section number="02" title="Holiday requirement" description="Where are they travelling, and when?">
                    <x-field label="Destination" name="destination" required wide>
                        <input id="destination" name="destination" value="{{ old('destination') }}" required autofocus
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Start date" name="start_date">
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="End date" name="end_date">
                        <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Adults" name="adults">
                        <input id="adults" name="adults" type="number" min="0" value="{{ old('adults') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Children" name="children">
                        <input id="children" name="children" type="number" min="0" value="{{ old('children') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Budget" name="budget">
                        <input id="budget" name="budget" type="number" step="0.01" min="0" value="{{ old('budget') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Hotel category" name="hotel_category">
                        <input id="hotel_category" name="hotel_category" value="{{ old('hotel_category') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Meal plan" name="meal_plan">
                        <input id="meal_plan" name="meal_plan" value="{{ old('meal_plan') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Transport" name="transport_required">
                        <label class="flex items-center gap-2 text-sm text-slate-700 py-2.5">
                            <input type="checkbox" name="transport_required" value="1" class="rounded border-slate-300">
                            Required
                        </label>
                    </x-field>
                </x-form-section>

                <x-form-section number="03" title="Planning notes" description="Preferences or anything else worth remembering.">
                    <x-field label="Notes" name="notes" wide>
                        <textarea id="notes" name="notes" rows="4"
                                  class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">{{ old('notes') }}</textarea>
                    </x-field>
                </x-form-section>

                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-5 pb-2">
                    <p class="text-xs text-slate-400">Changes are saved only when you save this enquiry.</p>
                    <div class="flex items-center gap-2 shrink-0 justify-end">
                        <x-button tag="a" href="{{ route('enquiries.index') }}" variant="secondary">Cancel</x-button>
                        <x-button type="submit">Save enquiry</x-button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
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
    </form>
@endsection

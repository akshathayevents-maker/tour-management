@extends('layouts.app')

@section('title', 'Add lead')

@section('content')
    <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-3">
        <a href="{{ route('leads.index') }}" class="hover:text-slate-700">Leads</a>
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
        <span class="text-slate-600">New lead</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">Capture a new travel opportunity</h2>
            <p class="mt-0.5 text-sm text-slate-500">Record the prospect's requirement so you can follow up and turn it into a booking.</p>
        </div>
        <x-button tag="a" href="{{ route('leads.index') }}" variant="secondary" size="sm">Back to leads</x-button>
    </div>

    <form method="POST" action="{{ route('leads.store') }}"
          oninput="document.getElementById('preview-name').textContent = document.getElementById('name').value.trim() || 'New lead';
                   document.getElementById('preview-dest').textContent = document.getElementById('destination').value.trim() || '—';">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-0">
                <x-form-section number="01" title="Lead profile" description="Who is this prospect, and how do we reach them?">
                    <x-field label="Name" name="name" required wide>
                        <input id="name" name="name" value="{{ old('name') }}" required autofocus
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-[15px] py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Phone" name="phone" required>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                </x-form-section>

                <x-form-section number="02" title="Lead source" description="How did this traveller find you?">
                    <div class="sm:col-span-2 flex flex-wrap gap-2">
                        @foreach (\App\Enums\LeadSource::cases() as $source)
                            <label class="cursor-pointer">
                                <input type="radio" name="source" value="{{ $source->value }}" class="peer sr-only" required @checked(old('source') === $source->value)>
                                <span class="block rounded-full border border-slate-300 px-3.5 py-1.5 text-[13px] font-medium text-slate-600 peer-checked:bg-brand-700 peer-checked:text-white peer-checked:border-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-1 transition-colors">
                                    {{ $source->label() }}
                                </span>
                            </label>
                        @endforeach
                        @error('source')
                            <p class="w-full text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </x-form-section>

                <x-form-section number="03" title="Travel requirement" description="Optional — fill in what you know now.">
                    <x-field label="Destination" name="destination">
                        <input id="destination" name="destination" value="{{ old('destination') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Approx. travel date" name="travel_month">
                        <input id="travel_month" name="travel_month" type="date" value="{{ old('travel_month') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Travellers" name="travellers_count">
                        <input id="travellers_count" name="travellers_count" type="number" min="1" value="{{ old('travellers_count') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                    <x-field label="Budget" name="budget">
                        <input id="budget" name="budget" type="number" step="0.01" min="0" value="{{ old('budget') }}"
                               class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">
                    </x-field>
                </x-form-section>

                <x-form-section number="04" title="Follow-up notes" description="Anything worth remembering for the next conversation.">
                    <x-field label="Notes" name="notes" wide>
                        <textarea id="notes" name="notes" rows="4"
                                  class="block w-full rounded-lg border-slate-300 shadow-sm text-sm py-2.5 px-3.5">{{ old('notes') }}</textarea>
                    </x-field>
                </x-form-section>

                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-5 pb-2">
                    <p class="text-xs text-slate-400">Changes are saved only when you save this lead.</p>
                    <div class="flex items-center gap-2 shrink-0 justify-end">
                        <x-button tag="a" href="{{ route('leads.index') }}" variant="secondary">Cancel</x-button>
                        <x-button type="submit">Save lead</x-button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <x-context-card title="Lead preview">
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-slate-400">Prospect</dt>
                            <dd id="preview-name" class="font-medium text-slate-900">{{ old('name') ?: 'New lead' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Requirement</dt>
                            <dd id="preview-dest" class="font-medium text-slate-900">{{ old('destination') ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Next step</dt>
                            <dd class="font-medium text-slate-900">Follow up &amp; qualify</dd>
                        </div>
                    </dl>
                </x-context-card>

                <x-context-card title="What happens next">
                    <ol class="space-y-3">
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-[10px] font-semibold">01</span>
                            <div><p class="text-sm font-medium text-slate-900">Lead captured</p><p class="text-xs text-slate-500">Opportunity is logged</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">02</span>
                            <div><p class="text-sm font-medium text-slate-900">Follow-up</p><p class="text-xs text-slate-500">Reach out and qualify interest</p></div>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="shrink-0 flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">03</span>
                            <div><p class="text-sm font-medium text-slate-900">Convert</p><p class="text-xs text-slate-500">Turn the lead into a customer</p></div>
                        </li>
                    </ol>
                </x-context-card>
            </div>
        </div>
    </form>
@endsection

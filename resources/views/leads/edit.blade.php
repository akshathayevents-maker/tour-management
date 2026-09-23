@extends('layouts.app')

@section('title', 'Edit lead')

@section('content')
    <x-page-header title="Edit lead" subtitle="{{ $lead->name }}" />

    <div class="max-w-3xl bg-white rounded-lg border border-slate-200 px-6">
        <form method="POST" action="{{ route('leads.update', $lead) }}">
            @csrf
            @method('PUT')

            <x-form-section title="Contact">
                <x-field label="Name" name="name" required wide>
                    <input id="name" name="name" value="{{ old('name', $lead->name) }}" required autofocus
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Phone" name="phone" required>
                    <input id="phone" name="phone" value="{{ old('phone', $lead->phone) }}" required
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Lead source" name="source" required>
                    <select id="source" name="source" required class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        @foreach (\App\Enums\LeadSource::cases() as $source)
                            <option value="{{ $source->value }}" @selected(old('source', $lead->source->value) === $source->value)>{{ $source->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Status" name="status" required>
                    <select id="status" name="status" required class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        @foreach (\App\Enums\LeadStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $lead->status->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
            </x-form-section>

            <x-form-section title="Travel">
                <x-field label="Destination" name="destination">
                    <input id="destination" name="destination" value="{{ old('destination', $lead->destination) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Approx. travel date" name="travel_month">
                    <input id="travel_month" name="travel_month" type="date" value="{{ old('travel_month', $lead->travel_month?->format('Y-m-d')) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Travellers" name="travellers_count">
                    <input id="travellers_count" name="travellers_count" type="number" min="1" value="{{ old('travellers_count', $lead->travellers_count) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Budget" name="budget">
                    <input id="budget" name="budget" type="number" step="0.01" min="0" value="{{ old('budget', $lead->budget) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Notes" name="notes" wide>
                    <textarea id="notes" name="notes" rows="3"
                              class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes', $lead->notes) }}</textarea>
                </x-field>
            </x-form-section>

            <div class="flex items-center justify-end gap-2 py-4 border-t border-slate-100">
                <x-button tag="a" href="{{ route('leads.show', $lead) }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </form>
    </div>
@endsection

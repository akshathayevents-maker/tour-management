@extends('layouts.app')

@section('title', 'Edit trip')

@section('content')
    <x-page-header title="Edit trip" subtitle="{{ $trip->destination }}" />

    <div class="max-w-3xl bg-white rounded-lg border border-slate-200 px-6">
        <form method="POST" action="{{ route('trips.update', $trip) }}">
            @csrf
            @method('PUT')

            <x-form-section title="Trip details">
                <x-field label="Destination" name="destination" required wide>
                    <input id="destination" name="destination" value="{{ old('destination', $trip->destination) }}" required
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Start date" name="start_date">
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($trip->start_date)->format('Y-m-d')) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="End date" name="end_date">
                    <input id="end_date" type="date" name="end_date" value="{{ old('end_date', optional($trip->end_date)->format('Y-m-d')) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Traveller count" name="travellers_count">
                    <input id="travellers_count" type="number" min="1" name="travellers_count" value="{{ old('travellers_count', $trip->travellers_count) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Status" name="status">
                    <select id="status" name="status" class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        @foreach (\App\Enums\TripStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $trip->status->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
            </x-form-section>

            <div class="flex items-center justify-end gap-2 py-4 border-t border-slate-100">
                <x-button tag="a" href="{{ route('trips.show', $trip) }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </form>
    </div>
@endsection

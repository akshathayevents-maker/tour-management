@extends('layouts.app')

@section('title', 'Edit trip')

@section('content')
    <x-page-header title="Edit trip" />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('trips.update', $trip) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="destination" class="block text-sm font-medium text-slate-700">Destination</label>
                <input id="destination" name="destination" value="{{ old('destination', $trip->destination) }}" required
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-slate-700">Start date</label>
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($trip->start_date)->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-slate-700">End date</label>
                    <input id="end_date" type="date" name="end_date" value="{{ old('end_date', optional($trip->end_date)->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
            </div>

            <div>
                <label for="travellers_count" class="block text-sm font-medium text-slate-700">Traveller count</label>
                <input id="travellers_count" type="number" min="1" name="travellers_count" value="{{ old('travellers_count', $trip->travellers_count) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    @foreach (\App\Enums\TripStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $trip->status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save changes
            </button>
        </form>
    </div>
@endsection

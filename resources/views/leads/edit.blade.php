@extends('layouts.app')

@section('title', 'Edit lead')

@section('content')
    <x-page-header title="Edit lead" />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('leads.update', $lead) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" value="{{ old('name', $lead->name) }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone', $lead->phone) }}" required
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="source" class="block text-sm font-medium text-slate-700">Lead source</label>
                <select id="source" name="source" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    @foreach (\App\Enums\LeadSource::cases() as $source)
                        <option value="{{ $source->value }}" @selected(old('source', $lead->source->value) === $source->value)>{{ $source->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    @foreach (\App\Enums\LeadStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $lead->status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="destination" class="block text-sm font-medium text-slate-700">Destination</label>
                <input id="destination" name="destination" value="{{ old('destination', $lead->destination) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="travel_month" class="block text-sm font-medium text-slate-700">Approx. travel date</label>
                <input id="travel_month" name="travel_month" type="date" value="{{ old('travel_month', $lead->travel_month?->format('Y-m-d')) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="travellers_count" class="block text-sm font-medium text-slate-700">Travellers</label>
                    <input id="travellers_count" name="travellers_count" type="number" min="1" value="{{ old('travellers_count', $lead->travellers_count) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label for="budget" class="block text-sm font-medium text-slate-700">Budget</label>
                    <input id="budget" name="budget" type="number" step="0.01" min="0" value="{{ old('budget', $lead->budget) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
            </div>
            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes', $lead->notes) }}</textarea>
            </div>
            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save changes
            </button>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Trips')

@section('content')
    <x-page-header title="Trips" subtitle="Everything being planned or run right now.">
        <x-slot:actions>
            <a href="{{ route('trips.create') }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                New trip
            </a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <select name="status" onchange="this.form.submit()"
                class="rounded-md border-slate-300 shadow-sm sm:text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </form>

    @if ($trips->isEmpty())
        <x-empty-state
            title="No trips yet."
            description="Start planning once an enquiry is ready to move forward."
            action-label="New trip"
            :action-url="route('trips.create')" />
    @else
        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Destination</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Customer</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Dates</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($trips as $trip)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('trips.show', $trip) }}" class="text-slate-900 font-medium hover:underline">
                                    {{ $trip->destination }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-slate-500">{{ $trip->customer->name }}</td>
                            <td class="px-4 py-2 text-slate-500">
                                {{ $trip->start_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-2">
                                <x-status-badge :color="$trip->status->badgeColor()" :label="$trip->status->label()" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $trips->links() }}</div>
    @endif
@endsection

@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
    <x-page-header title="Bookings" subtitle="Confirmed commitments being operated.">
        <x-slot:actions>
            <a href="{{ route('bookings.create') }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                New booking
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

    @if ($bookings->isEmpty())
        <x-empty-state
            title="No bookings yet."
            description="Create one directly, or from an accepted quotation."
            action-label="New booking"
            :action-url="route('bookings.create')" />
    @else
        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Trip</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Customer</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Created</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($bookings as $booking)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('bookings.show', $booking) }}" class="text-slate-900 font-medium hover:underline">
                                    {{ $booking->trip->destination }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-slate-500">{{ $booking->customer->name }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ $booking->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-2">
                                <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $bookings->links() }}</div>
    @endif
@endsection

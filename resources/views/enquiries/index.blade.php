@extends('layouts.app')

@section('title', 'Enquiries')

@section('content')
    <x-page-header title="Enquiries" subtitle="What customers actually want to travel for.">
        <x-slot:actions>
            <a href="{{ route('enquiries.create') }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Add enquiry
            </a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4">
        <select name="status" onchange="this.form.submit()" class="rounded-md border-slate-300 shadow-sm sm:text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </form>

    @if ($enquiries->isEmpty())
        <x-empty-state
            title="No enquiries yet."
            description="Log a customer's travel requirement to start planning their trip."
            action-label="Add enquiry"
            :action-url="route('enquiries.create')" />
    @else
        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Customer</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Destination</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Travel dates</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($enquiries as $enquiry)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('enquiries.show', $enquiry) }}" class="text-slate-900 font-medium hover:underline">
                                    {{ $enquiry->customer->name }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-slate-500">{{ $enquiry->destination }}</td>
                            <td class="px-4 py-2 text-slate-500">
                                {{ $enquiry->start_date?->format('d M Y') ?? '—' }}
                                @if ($enquiry->end_date) &ndash; {{ $enquiry->end_date->format('d M Y') }} @endif
                            </td>
                            <td class="px-4 py-2"><x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $enquiries->links() }}</div>
    @endif
@endsection

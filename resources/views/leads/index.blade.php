@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    <x-page-header title="Leads" subtitle="Potential trips, before they're customers yet.">
        <x-slot:actions>
            <a href="{{ route('leads.create') }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Add lead
            </a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, phone or destination…"
               class="w-full sm:w-72 rounded-md border-slate-300 shadow-sm sm:text-sm">
        <select name="status" onchange="this.form.submit()" class="rounded-md border-slate-300 shadow-sm sm:text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </form>

    @if ($leads->isEmpty())
        <x-empty-state
            title="No leads yet."
            description="Capture a lead the moment someone reaches out on Instagram, WhatsApp or a walk-in."
            action-label="Add lead"
            :action-url="route('leads.create')" />
    @else
        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Destination</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Source</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Status</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Added</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($leads as $lead)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('leads.show', $lead) }}" class="text-slate-900 font-medium hover:underline">
                                    {{ $lead->name }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-slate-500">{{ $lead->destination ?? '—' }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ $lead->source->label() }}</td>
                            <td class="px-4 py-2"><x-status-badge :color="$lead->status->badgeColor()" :label="$lead->status->label()" /></td>
                            <td class="px-4 py-2 text-slate-500">{{ $lead->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $leads->links() }}</div>
    @endif
@endsection

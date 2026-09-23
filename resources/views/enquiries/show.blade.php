@extends('layouts.app')

@section('title', $enquiry->destination)

@section('content')
    <x-page-header :title="$enquiry->destination" subtitle="For {{ $enquiry->customer->name }}">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('trips.create', ['enquiry_id' => $enquiry->id]) }}">Start planning</x-button>
            <x-button tag="a" href="{{ route('enquiries.edit', $enquiry) }}" variant="secondary">Edit</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5"><x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" /></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-summary-panel title="Customer">
                <dl class="divide-y divide-slate-50">
                    <x-detail-row label="Name"><a href="{{ route('customers.show', $enquiry->customer) }}" class="hover:underline">{{ $enquiry->customer->name }}</a></x-detail-row>
                    <x-detail-row label="Phone">{{ $enquiry->customer->phone }}</x-detail-row>
                </dl>
                @if ($enquiry->lead)
                    <p class="mt-3 text-xs text-slate-400 border-t border-slate-100 pt-3">
                        Originated from a <a href="{{ route('leads.show', $enquiry->lead) }}" class="underline">lead</a>.
                    </p>
                @endif
            </x-summary-panel>

            <x-summary-panel title="Travel requirements">
                <dl class="divide-y divide-slate-50">
                    <x-detail-row label="Travel dates">
                        {{ $enquiry->start_date?->format('d M Y') ?? '—' }}
                        @if ($enquiry->end_date) &ndash; {{ $enquiry->end_date->format('d M Y') }} @endif
                    </x-detail-row>
                    <x-detail-row label="Travellers">
                        @if ($enquiry->adults || $enquiry->children)
                            {{ $enquiry->adults ?? 0 }} adults, {{ $enquiry->children ?? 0 }} children
                        @else — @endif
                    </x-detail-row>
                    <x-detail-row label="Budget">{{ $enquiry->budget ? number_format($enquiry->budget, 2) : '—' }}</x-detail-row>
                    <x-detail-row label="Hotel category">{{ $enquiry->hotel_category ?? '—' }}</x-detail-row>
                    <x-detail-row label="Meal plan">{{ $enquiry->meal_plan ?? '—' }}</x-detail-row>
                    <x-detail-row label="Transport">{{ $enquiry->transport_required === null ? '—' : ($enquiry->transport_required ? 'Required' : 'Not required') }}</x-detail-row>
                </dl>
                @if ($enquiry->notes)
                    <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $enquiry->notes }}</p>
                @endif
            </x-summary-panel>
        </div>

        <div class="space-y-6">
            <x-summary-panel title="Follow-ups">
                @include('follow_ups._quick-add', ['subjectType' => 'enquiry', 'subjectId' => $enquiry->id])
                @forelse ($followUps as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp, 'hideSubject' => true])
                @empty
                    <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                @endforelse
            </x-summary-panel>
        </div>
    </div>
@endsection

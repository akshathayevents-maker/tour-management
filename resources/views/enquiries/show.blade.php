@extends('layouts.app')

@section('title', $enquiry->destination)

@section('content')
    <x-page-header :title="$enquiry->destination" subtitle="For {{ $enquiry->customer->name }}">
        <x-slot:actions>
            <a href="{{ route('enquiries.edit', $enquiry) }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Edit
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-sm font-medium text-slate-900">Requirement</h3>
                    <x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" />
                </div>
                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-slate-500">Customer</dt>
                    <dd class="text-slate-900"><a href="{{ route('customers.show', $enquiry->customer) }}" class="hover:underline">{{ $enquiry->customer->name }}</a></dd>
                    <dt class="text-slate-500">Travel dates</dt>
                    <dd class="text-slate-900">
                        {{ $enquiry->start_date?->format('d M Y') ?? '—' }}
                        @if ($enquiry->end_date) &ndash; {{ $enquiry->end_date->format('d M Y') }} @endif
                    </dd>
                    <dt class="text-slate-500">Travellers</dt>
                    <dd class="text-slate-900">
                        @if ($enquiry->adults || $enquiry->children)
                            {{ $enquiry->adults ?? 0 }} adults, {{ $enquiry->children ?? 0 }} children
                        @else — @endif
                    </dd>
                    <dt class="text-slate-500">Budget</dt><dd class="text-slate-900">{{ $enquiry->budget ? number_format($enquiry->budget, 2) : '—' }}</dd>
                    <dt class="text-slate-500">Hotel category</dt><dd class="text-slate-900">{{ $enquiry->hotel_category ?? '—' }}</dd>
                    <dt class="text-slate-500">Meal plan</dt><dd class="text-slate-900">{{ $enquiry->meal_plan ?? '—' }}</dd>
                    <dt class="text-slate-500">Transport</dt><dd class="text-slate-900">{{ $enquiry->transport_required === null ? '—' : ($enquiry->transport_required ? 'Required' : 'Not required') }}</dd>
                </dl>
                @if ($enquiry->notes)
                    <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $enquiry->notes }}</p>
                @endif
                @if ($enquiry->lead)
                    <p class="mt-3 text-xs text-slate-400 border-t border-slate-100 pt-3">
                        Originated from a <a href="{{ route('leads.show', $enquiry->lead) }}" class="underline">lead</a>.
                    </p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Follow-ups</h3>
                @include('follow_ups._quick-add', ['subjectType' => 'enquiry', 'subjectId' => $enquiry->id])
                @forelse ($followUps as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp, 'hideSubject' => true])
                @empty
                    <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

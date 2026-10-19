@extends('layouts.app')

@section('title', $enquiry->destination)

@section('content')
    {{-- Enquiry dossier hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" class="!bg-white/10 !text-white !ring-white/20" />
                    @if ($enquiry->lead)
                        <a href="{{ route('leads.show', $enquiry->lead) }}" class="text-xs text-brand-300 hover:text-white">
                            Originated from a lead
                        </a>
                    @endif
                </div>
                <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">{{ $enquiry->destination }}</h2>
                <p class="mt-1 text-sm text-brand-200">
                    For <a href="{{ route('customers.show', $enquiry->customer) }}" class="text-white hover:underline">{{ $enquiry->customer->name }}</a>
                    &middot; {{ $enquiry->customer->phone }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-button tag="a" href="{{ route('trips.create', ['enquiry_id' => $enquiry->id]) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">Start planning</x-button>
                <x-button tag="a" href="{{ route('enquiries.edit', $enquiry) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">Edit</x-button>
            </div>
        </div>

        <div class="relative mt-5 flex flex-wrap gap-x-8 gap-y-3 border-t border-white/10 pt-4">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Travel dates</p>
                <p class="text-sm font-medium text-white mt-0.5">
                    {{ $enquiry->start_date?->format('d M Y') ?? 'Not set' }}
                    @if ($enquiry->end_date) &ndash; {{ $enquiry->end_date->format('d M Y') }} @endif
                </p>
            </div>
            @if ($enquiry->adults || $enquiry->children)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Travellers</p>
                    <p class="text-sm font-medium text-white mt-0.5">{{ $enquiry->adults ?? 0 }} adults, {{ $enquiry->children ?? 0 }} children</p>
                </div>
            @endif
            @if ($enquiry->budget)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Budget</p>
                    <p class="text-sm font-medium text-accent-400 mt-0.5">{{ number_format($enquiry->budget, 2) }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-summary-panel title="Travel requirements">
                <dl class="divide-y divide-slate-50">
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

            {{-- Activity --}}
            @if ($activities->isNotEmpty())
                <x-summary-panel title="Activity">
                    <div class="relative">
                        @foreach ($activities as $activity)
                            <div class="flex gap-3 pb-3 last:pb-0">
                                <div class="flex flex-col items-center shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-400 mt-1.5"></span>
                                    @if (! $loop->last)<span class="w-px flex-1 bg-slate-100 mt-1"></span>@endif
                                </div>
                                <div class="flex-1 min-w-0 text-sm">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-600">{{ $activity->description }}</span>
                                        <span class="text-xs text-slate-400 shrink-0">{{ $activity->created_at->diffForHumans() }}</span>
                                    </div>
                                    <span class="text-xs text-slate-400">by {{ $activity->causer->name ?? 'System' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-summary-panel>
            @endif
        </div>
    </div>
@endsection

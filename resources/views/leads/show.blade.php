@extends('layouts.app')

@section('title', $lead->name)

@section('content')
    {{-- Lead dossier hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-avatar :name="$lead->name" class="ring-2 ring-white/10 !bg-white/10 !text-white shrink-0" />
                <div>
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <x-status-badge :color="$lead->status->badgeColor()" :label="$lead->status->label()" class="!bg-white/10 !text-white !ring-white/20" />
                        @if ($lead->isConverted())
                            <span class="text-xs text-emerald-300">
                                Converted to <a href="{{ route('customers.show', $lead->customer) }}" class="font-medium text-white hover:underline">{{ $lead->customer->name }}</a>
                                &middot; {{ $lead->converted_at->format('d M Y') }}
                            </span>
                        @endif
                    </div>
                    <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">{{ $lead->name }}</h2>
                    <p class="mt-1 text-sm text-brand-200">Lead via {{ $lead->source->label() }} &middot; {{ $lead->created_at->format('d M Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if (! $lead->isConverted())
                    <form method="POST" action="{{ route('leads.convert', $lead) }}"
                          onsubmit="return confirm('Convert this lead to a customer?');">
                        @csrf
                        <button class="rounded-md bg-emerald-500 text-white text-[13px] font-medium px-3.5 py-2 hover:bg-emerald-600 shadow-sm">
                            Convert to customer
                        </button>
                    </form>
                @endif
                <x-button tag="a" href="{{ route('leads.edit', $lead) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">Edit</x-button>
            </div>
        </div>

        <div class="relative mt-5 flex flex-wrap gap-x-8 gap-y-3 border-t border-white/10 pt-4">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Destination</p>
                <p class="text-sm font-medium text-white mt-0.5">{{ $lead->destination ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Approx. travel date</p>
                <p class="text-sm font-medium text-white mt-0.5">{{ $lead->travel_month?->format('d M Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Travellers</p>
                <p class="text-sm font-medium text-white mt-0.5">{{ $lead->travellers_count ?? '—' }}</p>
            </div>
            @if ($lead->budget)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Budget</p>
                    <p class="text-sm font-medium text-accent-400 mt-0.5">{{ number_format($lead->budget, 2) }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-summary-panel title="Contact">
                <dl class="divide-y divide-slate-50">
                    <x-detail-row label="Phone">{{ $lead->phone }}</x-detail-row>
                    <x-detail-row label="Source">{{ $lead->source->label() }}</x-detail-row>
                </dl>
                @if ($lead->notes)
                    <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $lead->notes }}</p>
                @endif
            </x-summary-panel>
        </div>

        <div class="space-y-6">
            <x-summary-panel title="Follow-ups">
                @include('follow_ups._quick-add', ['subjectType' => 'lead', 'subjectId' => $lead->id])
                @forelse ($followUps as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp, 'hideSubject' => true])
                @empty
                    <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                @endforelse
            </x-summary-panel>
        </div>
    </div>
@endsection

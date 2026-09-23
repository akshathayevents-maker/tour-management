@extends('layouts.app')

@section('title', $lead->name)

@section('content')
    <x-page-header :title="$lead->name" subtitle="Lead via {{ $lead->source->label() }} · {{ $lead->created_at->format('d M Y') }}">
        <x-slot:actions>
            @if (! $lead->isConverted())
                <form method="POST" action="{{ route('leads.convert', $lead) }}"
                      onsubmit="return confirm('Convert this lead to a customer?');">
                    @csrf
                    <button class="rounded-md bg-green-600 text-white text-sm font-medium px-4 py-2 hover:bg-green-700">
                        Convert to customer
                    </button>
                </form>
            @endif
            <a href="{{ route('leads.edit', $lead) }}"
               class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                Edit
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-sm font-medium text-slate-900">Lead details</h3>
                    <x-status-badge :color="$lead->status->badgeColor()" :label="$lead->status->label()" />
                </div>
                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-slate-500">Phone</dt><dd class="text-slate-900">{{ $lead->phone }}</dd>
                    <dt class="text-slate-500">Destination</dt><dd class="text-slate-900">{{ $lead->destination ?? '—' }}</dd>
                    <dt class="text-slate-500">Approx. travel date</dt><dd class="text-slate-900">{{ $lead->travel_month?->format('d M Y') ?? '—' }}</dd>
                    <dt class="text-slate-500">Travellers</dt><dd class="text-slate-900">{{ $lead->travellers_count ?? '—' }}</dd>
                    <dt class="text-slate-500">Budget</dt><dd class="text-slate-900">{{ $lead->budget ? number_format($lead->budget, 2) : '—' }}</dd>
                </dl>
                @if ($lead->notes)
                    <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $lead->notes }}</p>
                @endif

                @if ($lead->isConverted())
                    <p class="mt-3 text-sm text-green-700 border-t border-slate-100 pt-3">
                        Converted to
                        <a href="{{ route('customers.show', $lead->customer) }}" class="font-medium hover:underline">{{ $lead->customer->name }}</a>
                        on {{ $lead->converted_at->format('d M Y') }}.
                    </p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Follow-ups</h3>
                @include('follow_ups._quick-add', ['subjectType' => 'lead', 'subjectId' => $lead->id])
                @forelse ($followUps as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp, 'hideSubject' => true])
                @empty
                    <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    <div class="flex items-center gap-3 mb-1">
        <x-avatar :name="$customer->name" size="lg" />
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ $customer->name }}</h2>
            <p class="text-sm text-slate-500">Customer since {{ $customer->created_at->format('d M Y') }}</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <x-button tag="a" href="{{ route('enquiries.create', ['customer_id' => $customer->id]) }}" variant="secondary">New enquiry</x-button>
            <x-button tag="a" href="{{ route('customers.edit', $customer) }}" variant="secondary">Edit</x-button>
        </div>
    </div>
    <div class="mb-6"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-summary-panel title="Contact details">
                <dl class="divide-y divide-slate-50">
                    <x-detail-row label="Phone">{{ $customer->phone }}</x-detail-row>
                    <x-detail-row label="Email">{{ $customer->email ?? '—' }}</x-detail-row>
                    <x-detail-row label="WhatsApp">{{ $customer->whatsapp ?? '—' }}</x-detail-row>
                    <x-detail-row label="City">{{ $customer->city ?? '—' }}</x-detail-row>
                    <x-detail-row label="Country">{{ $customer->country ?? '—' }}</x-detail-row>
                </dl>
                @if ($customer->notes)
                    <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $customer->notes }}</p>
                @endif
            </x-summary-panel>

            <x-summary-panel title="Enquiries">
                @forelse ($customer->enquiries as $enquiry)
                    <a href="{{ route('enquiries.show', $enquiry) }}"
                       class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                        <span class="text-slate-900">{{ $enquiry->destination }}</span>
                        <x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" />
                    </a>
                @empty
                    <p class="text-sm text-slate-400">No enquiries yet.</p>
                @endforelse
            </x-summary-panel>

            @if ($customer->leads->isNotEmpty())
                <x-summary-panel title="Originating leads">
                    @foreach ($customer->leads as $lead)
                        <a href="{{ route('leads.show', $lead) }}"
                           class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                            <span class="text-slate-900">{{ $lead->source->label() }}</span>
                            <span class="text-slate-400">{{ $lead->created_at->format('d M Y') }}</span>
                        </a>
                    @endforeach
                </x-summary-panel>
            @endif
        </div>

        <div class="space-y-6">
            <x-summary-panel title="Follow-ups">
                @include('follow_ups._quick-add', ['subjectType' => 'customer', 'subjectId' => $customer->id])
                @forelse ($followUps as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp])
                @empty
                    <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                @endforelse
            </x-summary-panel>
        </div>
    </div>
@endsection

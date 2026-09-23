@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    <x-page-header :title="$customer->name" subtitle="Customer since {{ $customer->created_at->format('d M Y') }}">
        <x-slot:actions>
            <a href="{{ route('enquiries.create', ['customer_id' => $customer->id]) }}"
               class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                New enquiry
            </a>
            <a href="{{ route('customers.edit', $customer) }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Edit
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Contact details</h3>
                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-slate-500">Phone</dt><dd class="text-slate-900">{{ $customer->phone }}</dd>
                    <dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ $customer->email ?? '—' }}</dd>
                    <dt class="text-slate-500">WhatsApp</dt><dd class="text-slate-900">{{ $customer->whatsapp ?? '—' }}</dd>
                    <dt class="text-slate-500">City</dt><dd class="text-slate-900">{{ $customer->city ?? '—' }}</dd>
                    <dt class="text-slate-500">Country</dt><dd class="text-slate-900">{{ $customer->country ?? '—' }}</dd>
                </dl>
                @if ($customer->notes)
                    <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $customer->notes }}</p>
                @endif
            </div>

            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Enquiries</h3>
                @forelse ($customer->enquiries as $enquiry)
                    <a href="{{ route('enquiries.show', $enquiry) }}"
                       class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                        <span class="text-slate-900">{{ $enquiry->destination }}</span>
                        <x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" />
                    </a>
                @empty
                    <p class="text-sm text-slate-400">No enquiries yet.</p>
                @endforelse
            </div>

            @if ($customer->leads->isNotEmpty())
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <h3 class="text-sm font-medium text-slate-900 mb-3">Originating leads</h3>
                    @foreach ($customer->leads as $lead)
                        <a href="{{ route('leads.show', $lead) }}"
                           class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                            <span class="text-slate-900">{{ $lead->source->label() }}</span>
                            <span class="text-slate-400">{{ $lead->created_at->format('d M Y') }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Follow-ups</h3>
                @include('follow_ups._quick-add', ['subjectType' => 'customer', 'subjectId' => $customer->id])
                @forelse ($followUps as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp])
                @empty
                    <p class="text-sm text-slate-400 mt-3">No pending follow-ups.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

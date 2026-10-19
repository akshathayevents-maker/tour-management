@extends('layouts.app')

@section('title', $customer->name)

@section('content')
    {{-- Customer dossier hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-avatar :name="$customer->name" class="ring-2 ring-white/10 !bg-white/10 !text-white shrink-0" />
                <div>
                    <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">{{ $customer->name }}</h2>
                    <p class="mt-1 text-sm text-brand-200">Customer since {{ $customer->created_at->format('d M Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button tag="a" href="{{ route('enquiries.create', ['customer_id' => $customer->id]) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">New enquiry</x-button>
                <x-button tag="a" href="{{ route('customers.edit', $customer) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">Edit</x-button>
            </div>
        </div>

        <div class="relative mt-5 flex flex-wrap gap-x-8 gap-y-3 border-t border-white/10 pt-4">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Phone</p>
                <p class="text-sm font-medium text-white mt-0.5">{{ $customer->phone }}</p>
            </div>
            @if ($customer->email)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Email</p>
                    <p class="text-sm font-medium text-white mt-0.5">{{ $customer->email }}</p>
                </div>
            @endif
            @if ($customer->city || $customer->country)
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Location</p>
                    <p class="text-sm font-medium text-white mt-0.5">{{ collect([$customer->city, $customer->country])->filter()->join(', ') }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if ($customer->whatsapp || $customer->notes)
                <x-summary-panel title="Additional details">
                    @if ($customer->whatsapp)
                        <dl class="divide-y divide-slate-50">
                            <x-detail-row label="WhatsApp">{{ $customer->whatsapp }}</x-detail-row>
                        </dl>
                    @endif
                    @if ($customer->notes)
                        <p class="{{ $customer->whatsapp ? 'mt-3 border-t border-slate-100 pt-3' : '' }} text-sm text-slate-600">{{ $customer->notes }}</p>
                    @endif
                </x-summary-panel>
            @endif

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

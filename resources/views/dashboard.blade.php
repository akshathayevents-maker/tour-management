@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if (auth()->user()->isSuperAdmin())
        <x-empty-state
            title="Welcome, {{ auth()->user()->name }}"
            description="Platform-wide metrics land here as more companies onboard." />
    @else
        @php
            $attentionItems = $overdueFollowUps->map(fn ($f) => ['followUp' => $f, 'overdue' => true])
                ->concat($todayFollowUps->map(fn ($f) => ['followUp' => $f, 'overdue' => false]));

            $activity = $recentLeads->map(fn ($l) => ['type' => 'lead', 'record' => $l, 'at' => $l->created_at])
                ->concat($recentEnquiries->map(fn ($e) => ['type' => 'enquiry', 'record' => $e, 'at' => $e->created_at]))
                ->sortByDesc('at')->take(6);
        @endphp

        {{-- Hero --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-7 sm:px-8 sm:py-8 mb-6">
            <svg class="absolute -right-6 -top-10 h-56 w-56 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
            <svg class="absolute right-16 bottom-2 h-16 w-16 text-accent-400/20 rotate-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M2 16l7-2 4-7 2 1-3 7 6-1 2-4 2 1-2 5-8 2-6 2z"/></svg>
            <div class="relative flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-400/90 mb-1.5">{{ now()->format('l, j F Y') }}</p>
                    <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">
                        {{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }}, {{ explode(' ', auth()->user()->name)[0] }}
                    </h2>
                    <p class="mt-1 text-sm text-brand-200">Here's what needs your attention across the business today.</p>
                </div>
                @hasanyrole('company_admin|company_user')
                    <div class="flex items-center gap-2">
                        <x-button tag="a" href="{{ route('leads.create') }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">+ Lead</x-button>
                        <x-button tag="a" href="{{ route('enquiries.create') }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">+ Enquiry</x-button>
                        <x-button tag="a" href="{{ route('bookings.create') }}" size="sm" variant="accent">+ Booking</x-button>
                    </div>
                @endhasanyrole
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
            <x-stat-card icon="leads" tone="info" label="New leads" :value="$newLeadsCount" hint="Last 7 days" />
            <x-stat-card icon="chat" tone="brand" label="New enquiries" :value="$newEnquiriesCount" hint="Last 7 days" />
            <x-stat-card icon="alert" :tone="$overdueFollowUps->isNotEmpty() ? 'danger' : 'default'" label="Overdue follow-ups" :value="$overdueFollowUps->count()" hint="Needs immediate action" />
            <x-stat-card icon="clock" :tone="$todayFollowUps->isNotEmpty() ? 'warning' : 'default'" label="Due today" :value="$todayFollowUps->count()" hint="Scheduled for today" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
            {{-- Attention --}}
            <div class="lg:col-span-3">
                <div class="flex items-center justify-between mb-2.5">
                    <h3 class="text-[13px] font-semibold text-slate-900">Needs attention today</h3>
                    <a href="{{ route('follow-ups.index') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">View all</a>
                </div>
                <div class="bg-white rounded-xl border border-slate-200/70 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                    @forelse ($attentionItems as $entry)
                        @php $followUp = $entry['followUp']; @endphp
                        <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-50 last:border-0">
                            <span class="shrink-0 flex h-9 w-9 items-center justify-center rounded-lg {{ $entry['overdue'] ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v4.638c0 .225.12.433.315.546l3.25 1.875a.75.75 0 00.75-1.3L10.75 9.06V5z" clip-rule="evenodd" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $followUp->subjectLabel() }}</p>
                                <p class="text-xs {{ $entry['overdue'] ? 'text-red-600 font-medium' : 'text-slate-500' }}">
                                    {{ $entry['overdue'] ? 'Overdue' : 'Due today' }} · {{ $followUp->due_at->format('g:i A') }}
                                    @if ($followUp->reason) &middot; {{ $followUp->reason }} @endif
                                </p>
                            </div>
                            <a href="{{ $followUp->subjectUrl() }}" class="shrink-0 text-xs font-medium text-slate-600 hover:text-brand-700 rounded-md border border-slate-200 px-2.5 py-1.5 hover:border-brand-200 hover:bg-brand-50/50 transition-colors">Open</a>
                        </div>
                    @empty
                        <div class="flex items-center gap-3 px-4 py-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-900">You're all caught up</p>
                                <p class="text-xs text-slate-500">No urgent follow-ups need your attention today.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent activity --}}
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-2.5">
                    <h3 class="text-[13px] font-semibold text-slate-900">Recent activity</h3>
                    <a href="{{ route('leads.index') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800">View all</a>
                </div>
                <div class="bg-white rounded-xl border border-slate-200/70 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                    @forelse ($activity as $entry)
                        @php
                            $record = $entry['record'];
                            $name = $entry['type'] === 'lead' ? $record->name : $record->customer->name;
                            $destination = $entry['type'] === 'lead' ? $record->destination : $record->destination;
                            $url = $entry['type'] === 'lead' ? route('leads.show', $record) : route('enquiries.show', $record);
                            $verb = $entry['type'] === 'lead' ? 'New lead' : 'New enquiry';
                        @endphp
                        <a href="{{ $url }}" class="flex items-center gap-2.5 px-4 py-2.5 border-b border-slate-50 last:border-0 hover:bg-slate-50/70">
                            <x-avatar :name="$name" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-900 truncate">{{ $name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $verb }} @if($destination) &middot; {{ $destination }} @endif &middot; {{ $entry['at']->diffForHumans() }}</p>
                            </div>
                            <x-status-badge :color="$record->status->badgeColor()" :label="$record->status->label()" />
                        </a>
                    @empty
                        <div class="flex items-center gap-3 px-4 py-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><circle cx="12" cy="12" r="9" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-900">No recent activity</p>
                                <p class="text-xs text-slate-500">New leads and enquiries will appear here.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
@endsection

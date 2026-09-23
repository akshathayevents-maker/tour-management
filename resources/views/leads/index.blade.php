@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <div class="relative flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-400/90 mb-1.5">Travel enquiries</p>
                <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">Leads</h2>
                <p class="mt-1 text-sm text-brand-200">Turn holiday enquiries into memorable journeys.</p>
            </div>
            <x-button tag="a" href="{{ route('leads.create') }}" variant="accent" size="sm">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                Add lead
            </x-button>
        </div>
    </div>

    {{-- Pipeline --}}
    <div class="mb-4 -mx-1 flex gap-1 overflow-x-auto pb-1">
        <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}"
           class="shrink-0 rounded-lg px-3.5 py-2 text-[13px] font-medium transition-colors {{ ! request('status') ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-white hover:text-slate-900 border border-transparent hover:border-slate-200' }}">
            All leads
        </a>
        @foreach ($statuses as $status)
            @php $isActive = request('status') === $status->value; @endphp
            <a href="{{ request()->fullUrlWithQuery(['status' => $status->value]) }}"
               class="shrink-0 flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-[13px] font-medium transition-colors {{ $isActive ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-white hover:text-slate-900 border border-transparent hover:border-slate-200' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-accent-400' : 'bg-slate-300' }}"></span>
                {{ $status->label() }}
            </a>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <form method="GET" class="mb-5 flex flex-wrap items-center gap-2 bg-white border border-slate-200/80 rounded-xl px-3 py-2.5">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search leads, travellers, phone numbers or destinations…"
                   class="w-full rounded-md border-slate-300 pl-8 shadow-sm text-sm">
        </div>
        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-600 text-[13px] font-medium px-3 py-1.5 hover:bg-slate-50">Search</button>
        @if (request()->anyFilled(['q', 'status']))
            <a href="{{ route('leads.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Clear</a>
        @endif
    </form>

    @if ($leads->isEmpty())
        <div class="relative overflow-hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
            <svg class="absolute left-1/2 top-6 h-24 w-24 -translate-x-1/2 text-slate-50" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
            <div class="relative">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">
                    {{ request()->anyFilled(['q', 'status']) ? 'No leads match this view' : 'Your next journey starts here' }}
                </h3>
                <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    @if (request()->anyFilled(['q', 'status']))
                        Try a different search or clear the filters.
                    @else
                        Capture holiday enquiries from WhatsApp, Instagram, referrals and walk-ins the moment they come in.
                    @endif
                </p>
                @unless (request()->anyFilled(['q', 'status']))
                    <x-button tag="a" href="{{ route('leads.create') }}" class="mt-4">+ Add your first lead</x-button>
                @endunless
            </div>
        </div>
    @else
        {{-- Desktop rows --}}
        <div class="hidden sm:block bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100">
            @foreach ($leads as $lead)
                @php
                    $nextFollowUp = $lead->followUps()->pending()->orderBy('due_at')->first();
                @endphp
                <a href="{{ route('leads.show', $lead) }}" class="group flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50/70 transition-colors">
                    <x-avatar :name="$lead->name" />

                    <div class="w-48 shrink-0 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $lead->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $lead->phone }}</p>
                    </div>

                    <div class="flex-1 min-w-0">
                        @if ($lead->destination)
                            <p class="text-sm text-slate-800 flex items-center gap-1.5 min-w-0">
                                <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                                <span class="truncate">{{ $lead->destination }}</span>
                            </p>
                        @else
                            <p class="text-sm text-slate-400 italic">Destination not decided</p>
                        @endif
                        <p class="text-xs text-slate-400 mt-0.5">
                            @if ($lead->travel_month) {{ $lead->travel_month->format('d M Y') }} @endif
                            @if ($lead->travel_month && $lead->travellers_count) &middot; @endif
                            @if ($lead->travellers_count) {{ $lead->travellers_count }} traveller{{ $lead->travellers_count > 1 ? 's' : '' }} @endif
                            @if (! $lead->travel_month && ! $lead->travellers_count) {{ $lead->source->label() }} @endif
                        </p>
                    </div>

                    <div class="w-40 shrink-0">
                        @if ($nextFollowUp)
                            <p class="text-xs font-medium {{ $nextFollowUp->isOverdue() ? 'text-red-600' : ($nextFollowUp->due_at->isToday() ? 'text-amber-700' : 'text-slate-500') }}">
                                {{ $nextFollowUp->isOverdue() ? 'Follow-up overdue' : ($nextFollowUp->due_at->isToday() ? 'Follow up today' : 'Follow up '.$nextFollowUp->due_at->format('d M')) }}
                            </p>
                        @else
                            <p class="text-xs text-slate-300">No follow-up scheduled</p>
                        @endif
                        <p class="text-xs text-slate-400 mt-0.5">{{ $lead->source->label() }}</p>
                    </div>

                    <div class="w-28 shrink-0 flex justify-end">
                        <x-status-badge :color="$lead->status->badgeColor()" :label="$lead->status->label()" />
                    </div>

                    <svg class="h-4 w-4 text-slate-300 group-hover:text-brand-600 group-hover:translate-x-0.5 transition-all shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                </a>
            @endforeach
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-2">
            @foreach ($leads as $lead)
                @php $nextFollowUp = $lead->followUps()->pending()->orderBy('due_at')->first(); @endphp
                <a href="{{ route('leads.show', $lead) }}" class="block bg-white rounded-xl border border-slate-200/80 p-3.5">
                    <div class="flex items-start gap-2.5">
                        <x-avatar :name="$lead->name" size="sm" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $lead->name }}</p>
                                <x-status-badge :color="$lead->status->badgeColor()" :label="$lead->status->label()" />
                            </div>
                            <p class="text-xs text-slate-500">{{ $lead->phone }}</p>

                            <p class="text-sm text-slate-800 mt-1.5 flex items-center gap-1.5 min-w-0">
                                @if ($lead->destination)
                                    <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                                    <span class="truncate">{{ $lead->destination }}</span>
                                @else
                                    <span class="text-slate-400 italic text-xs">Destination not decided</span>
                                @endif
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                @if ($lead->travel_month) {{ $lead->travel_month->format('d M Y') }} &middot; @endif
                                @if ($lead->travellers_count) {{ $lead->travellers_count }} travellers &middot; @endif
                                {{ $lead->source->label() }}
                            </p>

                            <div class="mt-2 pt-2 border-t border-slate-50 flex items-center justify-between">
                                @if ($nextFollowUp)
                                    <span class="text-xs font-medium {{ $nextFollowUp->isOverdue() ? 'text-red-600' : ($nextFollowUp->due_at->isToday() ? 'text-amber-700' : 'text-slate-500') }}">
                                        {{ $nextFollowUp->isOverdue() ? 'Overdue' : ($nextFollowUp->due_at->isToday() ? 'Due today' : $nextFollowUp->due_at->format('d M')) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300">No follow-up</span>
                                @endif
                                <span class="text-xs text-slate-400">{{ $lead->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-4">{{ $leads->links() }}</div>
    @endif
@endsection

@extends('layouts.app')

@section('title', 'Follow-ups')

@section('content')
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <p class="relative text-[11px] font-semibold uppercase tracking-widest text-accent-400/90 mb-1.5">Today's action centre</p>
        <h2 class="relative text-2xl sm:text-[26px] font-semibold tracking-tight text-white">Follow-ups</h2>
        <p class="relative mt-1 text-sm text-brand-200 max-w-lg">Keep enquiries moving, respond at the right time, and never lose a customer because a follow-up was missed.</p>
    </div>

    {{-- Summary strip --}}
    <div class="grid grid-cols-3 gap-3.5 mb-6">
        <div class="bg-white rounded-xl border border-slate-200/80 p-4">
            <div class="flex items-center justify-between">
                <p class="text-[12.5px] font-medium text-slate-500">Overdue</p>
                <span class="flex h-7 w-7 items-center justify-center rounded-md {{ $overdue->isNotEmpty() ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-400' }}">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>
                </span>
            </div>
            <p class="mt-2 text-[26px] leading-none font-semibold tabular-nums {{ $overdue->isNotEmpty() ? 'text-red-700' : 'text-slate-900' }}">{{ $overdue->count() }}</p>
            <p class="mt-1.5 text-xs text-slate-400">Needs attention</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-4">
            <div class="flex items-center justify-between">
                <p class="text-[12.5px] font-medium text-slate-500">Today</p>
                <span class="flex h-7 w-7 items-center justify-center rounded-md {{ $dueToday->isNotEmpty() ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-400' }}">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5zM12.75 7.5a.75.75 0 00-1.5 0v5c0 .27.144.518.378.651l3.5 2a.75.75 0 00.744-1.302l-3.122-1.784V7.5z" clip-rule="evenodd" /></svg>
                </span>
            </div>
            <p class="mt-2 text-[26px] leading-none font-semibold tabular-nums text-slate-900">{{ $dueToday->count() }}</p>
            <p class="mt-1.5 text-xs text-slate-400">Due today</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-4">
            <div class="flex items-center justify-between">
                <p class="text-[12.5px] font-medium text-slate-500">Upcoming</p>
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-50 text-brand-600">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 017.5 3v1.5h9V3a.75.75 0 011.5 0v1.5h.75a3 3 0 013 3v11.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V7.5a3 3 0 013-3H6V3a.75.75 0 01.75-.75zM4.5 9v9.75a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5V9h-16.5z" clip-rule="evenodd" /></svg>
                </span>
            </div>
            <p class="mt-2 text-[26px] leading-none font-semibold tabular-nums text-slate-900">{{ $upcoming->count() }}</p>
            <p class="mt-1.5 text-xs text-slate-400">What's next</p>
        </div>
    </div>

    @php
        $isEmpty = $overdue->isEmpty() && $dueToday->isEmpty() && $upcoming->isEmpty();

        $destinationContext = function ($followUp) {
            $subject = $followUp->followupable;
            return match (true) {
                $subject instanceof \App\Models\Enquiry, $subject instanceof \App\Models\Trip => $subject->destination,
                default => null,
            };
        };
    @endphp

    @if ($isEmpty)
        <div class="relative overflow-hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
            <div class="relative">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">You're all caught up</h3>
                <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">No follow-ups need your attention right now. Your next customer conversation will appear here.</p>
            </div>
        </div>
    @else
        <div class="space-y-8">
            @if ($overdue->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-2.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                        <h3 class="text-[13px] font-semibold text-red-700 uppercase tracking-wide">Overdue</h3>
                    </div>
                    <div class="bg-white rounded-xl border border-red-100 divide-y divide-red-50">
                        @foreach ($overdue as $followUp)
                            @include('follow_ups._row', ['followUp' => $followUp, 'urgency' => 'overdue', 'destination' => $destinationContext($followUp)])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($dueToday->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-2.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        <h3 class="text-[13px] font-semibold text-slate-900 uppercase tracking-wide">Today &middot; {{ now()->format('j F') }}</h3>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100">
                        @foreach ($dueToday as $followUp)
                            @include('follow_ups._row', ['followUp' => $followUp, 'urgency' => 'today', 'destination' => $destinationContext($followUp)])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($upcoming->isNotEmpty())
                <div>
                    <div class="flex items-center gap-2 mb-2.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                        <h3 class="text-[13px] font-semibold text-slate-500 uppercase tracking-wide">Upcoming</h3>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100 opacity-90">
                        @foreach ($upcoming as $followUp)
                            @include('follow_ups._row', ['followUp' => $followUp, 'urgency' => 'upcoming', 'destination' => $destinationContext($followUp)])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection

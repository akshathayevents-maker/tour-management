@extends('layouts.app')

@section('title', 'Trips')

@section('content')
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <div class="relative flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-400/90 mb-1.5">Journeys in motion</p>
                <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">Trips</h2>
                <p class="mt-1 text-sm text-brand-200">Manage customer journeys, travel dates and trip operations.</p>
            </div>
            <x-button tag="a" href="{{ route('trips.create') }}" variant="accent" size="sm">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                Create trip
            </x-button>
        </div>
    </div>

    {{-- Status filter --}}
    <div class="mb-5 -mx-1 flex gap-1 overflow-x-auto pb-1">
        <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}"
           class="shrink-0 rounded-lg px-3.5 py-2 text-[13px] font-medium transition-colors {{ ! request('status') ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-white hover:text-slate-900 border border-transparent hover:border-slate-200' }}">
            All trips
        </a>
        @foreach ($statuses as $status)
            @php $isActive = request('status') === $status->value; @endphp
            <a href="{{ request()->fullUrlWithQuery(['status' => $status->value]) }}"
               class="shrink-0 flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-[13px] font-medium transition-colors {{ $isActive ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-white hover:text-slate-900 border border-transparent hover:border-slate-200' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-accent-400' : 'bg-slate-300' }}"></span>
                {{ $status->label() }}
            </a>
        @endforeach
        @if (request()->filled('status'))
            <a href="{{ route('trips.index') }}" class="shrink-0 flex items-center rounded-lg px-3.5 py-2 text-[13px] font-medium text-slate-400 hover:text-slate-900">Clear</a>
        @endif
    </div>

    @if ($trips->isEmpty())
        <div class="relative overflow-hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
            <svg class="absolute left-1/2 top-6 h-24 w-24 -translate-x-1/2 text-slate-50" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
            <div class="relative">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">
                    {{ request()->filled('status') ? 'No trips in this stage' : 'No trips yet' }}
                </h3>
                <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    @if (request()->filled('status'))
                        Try a different stage or view all trips.
                    @else
                        Create a trip to start planning a customer's journey.
                    @endif
                </p>
                @unless (request()->filled('status'))
                    <x-button tag="a" href="{{ route('trips.create') }}" class="mt-4">+ Create trip</x-button>
                @endunless
            </div>
        </div>
    @else
        {{-- Desktop rows --}}
        <div class="hidden sm:block bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100">
            @foreach ($trips as $trip)
                <a href="{{ route('trips.show', $trip) }}" class="group flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50/70 transition-colors">
                    <x-avatar :name="$trip->customer->name" />

                    <div class="w-48 shrink-0 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $trip->customer->name }}</p>
                        @if ($trip->travellers_count)
                            <p class="text-xs text-slate-400">{{ $trip->travellers_count }} traveller{{ $trip->travellers_count > 1 ? 's' : '' }}</p>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900 flex items-center gap-1.5 min-w-0">
                            <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                            <span class="truncate uppercase tracking-wide">{{ $trip->destination }}</span>
                        </p>
                    </div>

                    <div class="w-48 shrink-0">
                        @if ($trip->start_date)
                            <p class="text-sm text-slate-700">
                                {{ $trip->start_date->format('d M') }}
                                @if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} @else {{ $trip->start_date->format('Y') }} @endif
                            </p>
                            @if ($trip->start_date && $trip->end_date)
                                <p class="text-xs text-slate-400 mt-0.5">{{ $trip->start_date->diffInDays($trip->end_date) }} day{{ $trip->start_date->diffInDays($trip->end_date) == 1 ? '' : 's' }}</p>
                            @endif
                        @else
                            <p class="text-sm text-slate-400 italic">Dates not set</p>
                        @endif
                    </div>

                    <div class="w-28 shrink-0 flex justify-end">
                        <x-status-badge :color="$trip->status->badgeColor()" :label="$trip->status->label()" />
                    </div>

                    <span class="shrink-0 flex items-center gap-1 text-xs font-medium text-slate-400 group-hover:text-brand-600 transition-colors">
                        View journey
                        <svg class="h-3.5 w-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-2">
            @foreach ($trips as $trip)
                <a href="{{ route('trips.show', $trip) }}" class="block bg-white rounded-xl border border-slate-200/80 p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <x-avatar :name="$trip->customer->name" size="sm" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $trip->customer->name }}</p>
                                <p class="text-sm font-semibold text-slate-900 flex items-center gap-1.5 mt-0.5 min-w-0">
                                    <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                                    <span class="truncate uppercase tracking-wide">{{ $trip->destination }}</span>
                                </p>
                            </div>
                        </div>
                        <x-status-badge :color="$trip->status->badgeColor()" :label="$trip->status->label()" />
                    </div>

                    <div class="mt-2.5 pt-2.5 border-t border-slate-50 flex items-center justify-between">
                        @if ($trip->start_date)
                            <span class="text-xs text-slate-500">
                                {{ $trip->start_date->format('d M') }}
                                @if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }} @else {{ $trip->start_date->format('Y') }} @endif
                                @if ($trip->travellers_count) &middot; {{ $trip->travellers_count }} traveller{{ $trip->travellers_count > 1 ? 's' : '' }} @endif
                            </span>
                        @else
                            <span class="text-xs text-slate-400 italic">Dates not set</span>
                        @endif
                        <span class="text-xs font-medium text-brand-700">View &rarr;</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-4">{{ $trips->links() }}</div>
    @endif
@endsection

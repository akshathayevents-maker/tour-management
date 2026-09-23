@extends('layouts.app')

@section('title', 'Enquiries')

@section('content')
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <div class="relative flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-400/90 mb-1.5">Travel requirements</p>
                <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">Enquiries</h2>
                <p class="mt-1 text-sm text-brand-200">Turn travel interest into memorable journeys.</p>
            </div>
            <x-button tag="a" href="{{ route('enquiries.create') }}" variant="accent" size="sm">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                Add enquiry
            </x-button>
        </div>
    </div>

    {{-- Pipeline --}}
    <div class="mb-4 -mx-1 flex gap-1 overflow-x-auto pb-1">
        <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}"
           class="shrink-0 rounded-lg px-3.5 py-2 text-[13px] font-medium transition-colors {{ ! request('status') ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-white hover:text-slate-900 border border-transparent hover:border-slate-200' }}">
            All enquiries
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
            <a href="{{ route('enquiries.index') }}" class="shrink-0 flex items-center rounded-lg px-3.5 py-2 text-[13px] font-medium text-slate-400 hover:text-slate-900">Clear</a>
        @endif
    </div>

    <div class="mb-5"></div>

    @if ($enquiries->isEmpty())
        <div class="relative overflow-hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
            <svg class="absolute left-1/2 top-6 h-24 w-24 -translate-x-1/2 text-slate-50" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
            <div class="relative">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">
                    {{ request()->filled('status') ? 'No enquiries in this stage' : 'Your next journey starts here' }}
                </h3>
                <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    @if (request()->filled('status'))
                        Try a different stage or view all enquiries.
                    @else
                        Capture travel requirements from WhatsApp, Instagram, referrals and walk-ins and turn them into memorable holidays.
                    @endif
                </p>
                @unless (request()->filled('status'))
                    <x-button tag="a" href="{{ route('enquiries.create') }}" class="mt-4">+ Add enquiry</x-button>
                @endunless
            </div>
        </div>
    @else
        {{-- Desktop rows --}}
        <div class="hidden sm:block bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100">
            @foreach ($enquiries as $enquiry)
                <a href="{{ route('enquiries.show', $enquiry) }}" class="group flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50/70 transition-colors">
                    <x-avatar :name="$enquiry->customer->name" />

                    <div class="w-48 shrink-0 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $enquiry->customer->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $enquiry->customer->phone }}</p>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900 flex items-center gap-1.5 min-w-0">
                            <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                            <span class="truncate uppercase tracking-wide">{{ $enquiry->destination }}</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            @if ($enquiry->start_date)
                                {{ $enquiry->start_date->format('d M Y') }}
                                @if ($enquiry->end_date) &ndash; {{ $enquiry->end_date->format('d M Y') }} @endif
                                @if ($enquiry->adults || $enquiry->children) &middot; @endif
                            @endif
                            @if ($enquiry->adults || $enquiry->children)
                                {{ $enquiry->adults ?? 0 }} adult{{ $enquiry->adults == 1 ? '' : 's' }}
                                @if ($enquiry->children) &middot; {{ $enquiry->children }} child{{ $enquiry->children > 1 ? 'ren' : '' }} @endif
                            @endif
                            @if (! $enquiry->start_date && ! $enquiry->adults && ! $enquiry->children)
                                Travel dates not set
                            @endif
                        </p>
                    </div>

                    <div class="w-32 shrink-0">
                        <p class="text-xs text-slate-500">{{ $enquiry->created_at->diffForHumans() }}</p>
                        @if ($enquiry->lead)
                            <p class="text-xs text-slate-400 mt-0.5">via {{ $enquiry->lead->source->label() }}</p>
                        @endif
                    </div>

                    <div class="w-28 shrink-0 flex justify-end">
                        <x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" />
                    </div>

                    <svg class="h-4 w-4 text-slate-300 group-hover:text-brand-600 group-hover:translate-x-0.5 transition-all shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                </a>
            @endforeach
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-2">
            @foreach ($enquiries as $enquiry)
                <a href="{{ route('enquiries.show', $enquiry) }}" class="block bg-white rounded-xl border border-slate-200/80 p-3.5">
                    <div class="flex items-start gap-2.5">
                        <x-avatar :name="$enquiry->customer->name" size="sm" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $enquiry->customer->name }}</p>
                                <x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" />
                            </div>
                            <p class="text-xs text-slate-500">{{ $enquiry->customer->phone }}</p>

                            <p class="text-sm font-semibold text-slate-900 mt-1.5 flex items-center gap-1.5 min-w-0">
                                <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                                <span class="truncate uppercase tracking-wide">{{ $enquiry->destination }}</span>
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                @if ($enquiry->start_date)
                                    {{ $enquiry->start_date->format('d M Y') }} &middot;
                                @endif
                                @if ($enquiry->adults || $enquiry->children)
                                    {{ $enquiry->adults ?? 0 }} adult{{ $enquiry->adults == 1 ? '' : 's' }}@if ($enquiry->children), {{ $enquiry->children }} child{{ $enquiry->children > 1 ? 'ren' : '' }}@endif
                                @endif
                            </p>

                            <div class="mt-2 pt-2 border-t border-slate-50 flex items-center justify-between">
                                @if ($enquiry->lead)
                                    <span class="text-xs text-slate-400">via {{ $enquiry->lead->source->label() }}</span>
                                @else
                                    <span></span>
                                @endif
                                <span class="text-xs text-slate-400">{{ $enquiry->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-4">{{ $enquiries->links() }}</div>
    @endif
@endsection

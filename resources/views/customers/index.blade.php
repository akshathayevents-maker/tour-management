@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>
        <div class="relative flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-400/90 mb-1.5">Traveller relationships</p>
                <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">Customers</h2>
                <p class="mt-1 text-sm text-brand-200">The people behind every journey.</p>
            </div>
            <x-button tag="a" href="{{ route('customers.create') }}" variant="accent" size="sm">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                Add customer
            </x-button>
        </div>
    </div>

    {{-- Search --}}
    <form method="GET" class="mb-5 flex flex-wrap items-center gap-2 bg-white border border-slate-200/80 rounded-xl px-3 py-2.5">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search customers by name, phone or email…"
                   class="w-full rounded-md border-slate-300 pl-8 shadow-sm text-sm">
        </div>
        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-600 text-[13px] font-medium px-3 py-1.5 hover:bg-slate-50">Search</button>
        @if (request()->filled('q'))
            <a href="{{ route('customers.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Clear</a>
        @endif
    </form>

    @if ($customers->isEmpty())
        <div class="relative overflow-hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
            <svg class="absolute left-1/2 top-6 h-24 w-24 -translate-x-1/2 text-slate-50" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122z" /></svg>
            <div class="relative">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">
                    {{ request()->filled('q') ? 'No customers match your search' : 'Your traveller list starts here' }}
                </h3>
                <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    @if (request()->filled('q'))
                        Try a different name, phone or email.
                    @else
                        Keep every customer relationship in one place, from the first enquiry to the final journey.
                    @endif
                </p>
                @unless (request()->filled('q'))
                    <x-button tag="a" href="{{ route('customers.create') }}" class="mt-4">+ Add customer</x-button>
                @endunless
            </div>
        </div>
    @else
        {{-- Desktop rows --}}
        <div class="hidden sm:block bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100">
            @foreach ($customers as $customer)
                @php $lastEnquiry = $customer->enquiries()->latest()->first(); @endphp
                <a href="{{ route('customers.show', $customer) }}" class="group flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50/70 transition-colors">
                    <x-avatar :name="$customer->name" />

                    <div class="w-52 shrink-0 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $customer->name }}</p>
                        <p class="text-xs text-slate-400 truncate">
                            {{ $customer->phone }}
                            @if ($customer->email) &middot; {{ $customer->email }} @endif
                        </p>
                    </div>

                    <div class="flex-1 min-w-0">
                        @if ($lastEnquiry)
                            <p class="text-sm font-semibold text-slate-900 flex items-center gap-1.5 min-w-0">
                                <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                                <span class="truncate uppercase tracking-wide">{{ $lastEnquiry->destination }}</span>
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">Last enquiry &middot; {{ $lastEnquiry->created_at->format('d M Y') }}</p>
                        @elseif ($customer->city || $customer->country)
                            <p class="text-sm text-slate-600">{{ collect([$customer->city, $customer->country])->filter()->join(', ') }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">No enquiries yet</p>
                        @else
                            <p class="text-sm text-slate-400 italic">No enquiries yet</p>
                        @endif
                    </div>

                    <div class="w-32 shrink-0 text-right">
                        <p class="text-xs text-slate-400">Customer since</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $customer->created_at->format('d M Y') }}</p>
                    </div>

                    <svg class="h-4 w-4 text-slate-300 group-hover:text-brand-600 group-hover:translate-x-0.5 transition-all shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                </a>
            @endforeach
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-2">
            @foreach ($customers as $customer)
                @php $lastEnquiry = $customer->enquiries()->latest()->first(); @endphp
                <a href="{{ route('customers.show', $customer) }}" class="block bg-white rounded-xl border border-slate-200/80 p-3.5">
                    <div class="flex items-start gap-2.5">
                        <x-avatar :name="$customer->name" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $customer->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $customer->phone }}</p>

                            @if ($lastEnquiry)
                                <p class="text-sm font-semibold text-slate-900 mt-1.5 flex items-center gap-1.5 min-w-0">
                                    <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" /></svg>
                                    <span class="truncate uppercase tracking-wide">{{ $lastEnquiry->destination }}</span>
                                </p>
                            @elseif ($customer->city || $customer->country)
                                <p class="text-xs text-slate-500 mt-1.5">{{ collect([$customer->city, $customer->country])->filter()->join(', ') }}</p>
                            @endif

                            <div class="mt-2 pt-2 border-t border-slate-50 flex items-center justify-between">
                                <span class="text-xs text-slate-400">{{ $lastEnquiry ? 'Last enquiry '.$lastEnquiry->created_at->format('d M') : 'No enquiries yet' }}</span>
                                <span class="text-xs text-slate-400">Since {{ $customer->created_at->format('M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-4">{{ $customers->links() }}</div>
    @endif
@endsection

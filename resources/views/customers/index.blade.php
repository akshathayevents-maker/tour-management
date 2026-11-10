@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    {{-- Introduction — plain, no enclosing card --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-500 mb-1">Customer relationships</p>
            <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900">Customers</h1>
            <p class="mt-1 text-sm text-slate-500">Every traveller, every booking, every journey — in one place.</p>
        </div>
        <x-button tag="a" href="{{ route('customers.create') }}" size="sm" class="shrink-0">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
            Add customer
        </x-button>
    </div>

    {{-- KPIs — flat row, numbers are the only strong element --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-200 border border-slate-200 rounded-lg mb-5">
        <a href="{{ route('customers.index') }}" class="px-4 py-3 hover:bg-slate-50/70 transition-colors">
            <p class="text-xs text-slate-500">Total customers</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($stats['total']) }}</p>
            <p class="text-[11px] text-slate-400">{{ $stats['addedThisMonth'] }} added this month</p>
        </a>

        <a href="{{ route('customers.index', ['filter' => 'with_bookings']) }}" class="px-4 py-3 hover:bg-slate-50/70 transition-colors">
            <p class="text-xs text-slate-500">With bookings</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($stats['withBookings']) }}</p>
            <p class="text-[11px] text-slate-400">{{ $stats['withBookingsPct'] }}% of all customers</p>
        </a>

        <a href="{{ route('customers.index', ['filter' => 'upcoming_trips']) }}" class="px-4 py-3 hover:bg-slate-50/70 transition-colors">
            <p class="text-xs text-slate-500">Upcoming trips</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ number_format($stats['upcomingTrips']) }}</p>
            <p class="text-[11px] text-slate-400 truncate">
                {{ $stats['nextTravelDate'] ? 'Next '.\Illuminate\Support\Carbon::parse($stats['nextTravelDate'])->format('d M Y') : 'None scheduled' }}
            </p>
        </a>

        <a href="{{ route('customers.index', ['filter' => 'with_balance']) }}" class="px-4 py-3 hover:bg-slate-50/70 transition-colors">
            <p class="text-xs text-slate-500">Outstanding balances</p>
            <x-currency :amount="$stats['outstandingTotal']" :tone="$stats['outstandingTotal'] > 0 ? 'warning' : 'default'" class="!text-xl !font-semibold mt-1 block" />
            <p class="text-[11px] text-slate-400">{{ $stats['withBalance'] }} {{ Str::plural('customer', $stats['withBalance']) }} due</p>
        </a>
    </div>

    {{-- Search + filters — understated, no enclosing card --}}
    <form method="GET" class="mb-5 flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name, phone or email…"
                   aria-label="Search customers by name, phone or email"
                   class="w-full rounded-lg border-slate-200 pl-9 text-sm py-2.5 shadow-none focus:border-brand-400 focus:ring-brand-400">
        </div>
        <select name="filter" onchange="this.form.submit()" aria-label="Filter customers" class="rounded-lg border-slate-200 text-sm py-2.5 shadow-none focus:border-brand-400 focus:ring-brand-400">
            <option value="">All customers</option>
            <option value="with_bookings" @selected(request('filter') === 'with_bookings')>With bookings</option>
            <option value="upcoming_trips" @selected(request('filter') === 'upcoming_trips')>Upcoming trips</option>
            <option value="with_balance" @selected(request('filter') === 'with_balance')>Outstanding balance</option>
            <option value="recent" @selected(request('filter') === 'recent')>Added this month</option>
        </select>
        @if (request()->anyFilled(['q', 'filter']))
            <a href="{{ route('customers.index') }}" class="text-xs font-medium text-slate-400 hover:text-slate-700">Clear</a>
        @endif
        <span class="ml-auto text-xs text-slate-400 shrink-0">{{ number_format($customers->total()) }} {{ Str::plural('result', $customers->total()) }}</span>
    </form>

    @if ($customers->isEmpty())
        <x-empty-state
            :title="request()->anyFilled(['q', 'filter']) ? 'No customers match your search' : 'Your traveller list starts here'"
            :description="request()->anyFilled(['q', 'filter']) ? 'Try a different name, phone, email or filter.' : 'Keep every customer relationship in one place, from the first enquiry to the final journey.'"
            :actionLabel="request()->anyFilled(['q', 'filter']) ? null : 'Add customer'"
            :actionUrl="request()->anyFilled(['q', 'filter']) ? null : route('customers.create')" />
    @else
        {{-- Desktop: clean row list --}}
        <div class="hidden lg:block border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-medium text-slate-400 border-b border-slate-200">
                        <th class="py-2.5 pl-4 pr-3 font-medium">Customer</th>
                        <th class="py-2.5 px-3 font-medium">Location</th>
                        <th class="py-2.5 px-3 font-medium">Travel</th>
                        <th class="py-2.5 px-3 font-medium text-right">Value</th>
                        <th class="py-2.5 px-3 font-medium text-right">Paid</th>
                        <th class="py-2.5 px-3 font-medium text-right">Outstanding</th>
                        <th class="py-2.5 px-3 font-medium">Since</th>
                        <th class="py-2.5 pl-3 pr-4 font-medium text-right">&nbsp;</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($customers as $customer)
                        @php $lastEnquiry = $customer->enquiries->first(); @endphp
                        <tr class="group relative hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 pl-4 pr-3">
                                <a href="{{ route('customers.show', $customer) }}" class="flex items-center gap-2.5 focus-visible:outline-none">
                                    <span class="absolute inset-0"></span>
                                    <x-avatar :name="$customer->name" size="sm" />
                                    <span class="min-w-0">
                                        <span class="block font-medium text-slate-900 truncate">{{ $customer->name }}</span>
                                        <span class="block text-xs text-slate-400 truncate">{{ $customer->phone }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="py-3 px-3 text-slate-500 whitespace-nowrap align-middle">
                                {{ collect([$customer->city, $customer->country])->filter()->join(', ') ?: '—' }}
                            </td>
                            <td class="py-3 px-3 align-middle">
                                @if ($customer->upcomingTrip)
                                    <p class="text-xs font-medium text-emerald-700 truncate max-w-[160px]">{{ $customer->upcomingTrip->destination }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $customer->upcomingTrip->start_date->format('d M Y') }} &middot; {{ $customer->bookings_count }} {{ Str::plural('booking', $customer->bookings_count) }}</p>
                                @elseif ($lastEnquiry)
                                    <p class="text-xs text-slate-500 truncate max-w-[160px]">{{ $lastEnquiry->destination }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $lastEnquiry->created_at->format('d M Y') }}</p>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right text-slate-700 tabular-nums whitespace-nowrap align-middle">
                                {{ $customer->bookings_count > 0 ? number_format($customer->totalBookingValue, 0) : '—' }}
                            </td>
                            <td class="py-3 px-3 text-right text-slate-500 tabular-nums whitespace-nowrap align-middle">
                                {{ $customer->bookings_count > 0 ? number_format($customer->totalCollected, 0) : '—' }}
                            </td>
                            <td class="py-3 px-3 text-right whitespace-nowrap align-middle">
                                @if ($customer->outstandingBalance > 0)
                                    <span class="font-medium text-amber-700 tabular-nums">{{ number_format($customer->outstandingBalance, 0) }}</span>
                                @elseif ($customer->bookings_count > 0)
                                    <span class="text-slate-300">—</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-400 whitespace-nowrap align-middle">{{ $customer->created_at->format('d M Y') }}</td>
                            <td class="relative py-3 pl-3 pr-4 text-right whitespace-nowrap align-middle">
                                @can('update', $customer)
                                    <a href="{{ route('customers.edit', $customer) }}" class="relative z-10 text-xs text-slate-400 hover:text-slate-700">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile: compact record cards --}}
        <div class="lg:hidden space-y-2.5">
            @foreach ($customers as $customer)
                @php $lastEnquiry = $customer->enquiries->first(); @endphp
                <div class="group relative rounded-lg border border-slate-200 p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ route('customers.show', $customer) }}" class="flex items-start gap-3 min-w-0 flex-1 focus-visible:outline-none">
                            <span class="absolute inset-0"></span>
                            <x-avatar :name="$customer->name" />
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900 truncate">{{ $customer->name }}</span>
                                <span class="block text-xs text-slate-400 mt-0.5 truncate">{{ $customer->phone }}</span>
                            </span>
                        </a>
                        @can('update', $customer)
                            <a href="{{ route('customers.edit', $customer) }}" class="relative z-10 shrink-0 text-xs text-slate-400 hover:text-slate-700 py-1">Edit</a>
                        @endcan
                    </div>

                    <div class="mt-2.5 pl-[42px] text-xs">
                        @if ($customer->upcomingTrip)
                            <span class="font-medium text-emerald-700">{{ $customer->upcomingTrip->destination }}</span>
                            <span class="text-slate-400"> &middot; {{ $customer->upcomingTrip->start_date->format('d M Y') }}</span>
                        @elseif ($lastEnquiry)
                            <span class="text-slate-500">{{ $lastEnquiry->destination }}</span>
                            <span class="text-slate-400"> &middot; {{ $lastEnquiry->created_at->format('d M Y') }}</span>
                        @else
                            <span class="text-slate-300">No activity yet</span>
                        @endif
                    </div>

                    <div class="mt-2.5 pl-[42px] flex items-center gap-4 text-xs">
                        <span class="text-slate-400">Value <span class="font-medium text-slate-700 tabular-nums">{{ $customer->bookings_count > 0 ? number_format($customer->totalBookingValue, 0) : '—' }}</span></span>
                        <span class="text-slate-400">Paid <span class="font-medium text-slate-700 tabular-nums">{{ $customer->bookings_count > 0 ? number_format($customer->totalCollected, 0) : '—' }}</span></span>
                        @if ($customer->outstandingBalance > 0)
                            <span class="text-slate-400">Due <span class="font-medium text-amber-700 tabular-nums">{{ number_format($customer->outstandingBalance, 0) }}</span></span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $customers->links() }}</div>
    @endif
@endsection

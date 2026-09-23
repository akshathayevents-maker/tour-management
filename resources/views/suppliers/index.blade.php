@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-5 sm:px-8 sm:py-6 mb-6">
        <svg class="absolute right-4 bottom-2 h-16 w-16 text-white/[0.06]" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 2.25a.75.75 0 000 1.5v16.5h-.75a.75.75 0 000 1.5h16.5a.75.75 0 000-1.5H19.5V3.75a.75.75 0 000-1.5h-15zM9 6a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm-.75 3.75A.75.75 0 019 9h1.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM9 12.75a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm3.75-6.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5h-1.5zM12.75 12a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zM9 16.5a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm3 .75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75z" /></svg>
        <div class="relative flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-400/90 mb-1.5">Partner network</p>
                <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">Suppliers</h2>
                <p class="mt-1 text-sm text-brand-200">Manage the hotels, transport providers, guides and other partners behind your trips.</p>
            </div>
            <x-button tag="a" href="{{ route('suppliers.create') }}" size="sm">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                Add supplier
            </x-button>
        </div>
    </div>

    {{-- Overview --}}
    @php
        $totalSuppliers = \App\Models\Supplier::count();
        $activeSuppliers = \App\Models\Supplier::where('is_active', true)->count();
        $inactiveSuppliers = $totalSuppliers - $activeSuppliers;
        $hotelCount = \App\Models\Supplier::where('type', \App\Enums\SupplierType::Hotel)->count();
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
        <x-stat-card icon="building" tone="brand" label="Total suppliers" :value="$totalSuppliers" hint="Your network" />
        <x-stat-card icon="check" tone="success" label="Active" :value="$activeSuppliers" hint="Available to book" />
        <x-stat-card icon="alert" tone="default" label="Inactive" :value="$inactiveSuppliers" hint="Not in use" />
        <x-stat-card icon="building" tone="info" label="Hotels" :value="$hotelCount" hint="Accommodation partners" />
    </div>

    {{-- Type filter --}}
    <div class="mb-4 -mx-1 flex gap-1 overflow-x-auto pb-1">
        <a href="{{ request()->fullUrlWithQuery(['type' => null]) }}"
           class="shrink-0 rounded-lg px-3.5 py-2 text-[13px] font-medium transition-colors {{ ! request('type') ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-white hover:text-slate-900 border border-transparent hover:border-slate-200' }}">
            All types
        </a>
        @foreach ($types as $type)
            @php $isActive = request('type') === $type->value; @endphp
            <a href="{{ request()->fullUrlWithQuery(['type' => $type->value]) }}"
               class="shrink-0 flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-[13px] font-medium transition-colors {{ $isActive ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-white hover:text-slate-900 border border-transparent hover:border-slate-200' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-accent-400' : 'bg-slate-300' }}"></span>
                {{ $type->label() }}
            </a>
        @endforeach
        @if (request()->filled('type'))
            <a href="{{ route('suppliers.index') }}" class="shrink-0 flex items-center rounded-lg px-3.5 py-2 text-[13px] font-medium text-slate-400 hover:text-slate-900">Clear</a>
        @endif
    </div>

    {{-- Search --}}
    <form method="GET" class="mb-5 flex flex-wrap items-center gap-2 bg-white border border-slate-200/80 rounded-xl px-3 py-2.5">
        <input type="hidden" name="type" value="{{ request('type') }}">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search suppliers by name…"
                   class="w-full rounded-md border-slate-300 pl-8 shadow-sm text-sm">
        </div>
        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-600 text-[13px] font-medium px-3 py-1.5 hover:bg-slate-50">Search</button>
        @if (request()->filled('q'))
            <a href="{{ request()->fullUrlWithQuery(['q' => null]) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Clear search</a>
        @endif
    </form>

    @if ($suppliers->isEmpty())
        <div class="relative overflow-hidden rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
            <svg class="absolute left-1/2 top-6 h-24 w-24 -translate-x-1/2 text-slate-50" viewBox="0 0 24 24" fill="currentColor"><path d="M3.375 4.5A2.625 2.625 0 00.75 7.125v9.75A2.625 2.625 0 003.375 19.5h.375a3 3 0 106 0h7.5a3 3 0 106 0h.375a1.125 1.125 0 001.125-1.125v-3.25a3.375 3.375 0 00-.988-2.386l-3.401-3.401A2.625 2.625 0 0018.512 8.25H16.5v-2.25A1.5 1.5 0 0015 4.5H3.375z" /></svg>
            <div class="relative">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.017a3.001 3.001 0 003.75.617M21.75 9.349V6.75A2.25 2.25 0 0019.5 4.5H4.5a2.25 2.25 0 00-2.25 2.25v2.599" /></svg>
                </span>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">
                    {{ request()->anyFilled(['q', 'type']) ? 'No suppliers match this view' : 'Build your supplier network' }}
                </h3>
                <p class="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    @if (request()->anyFilled(['q', 'type']))
                        Try a different search or clear the filters.
                    @else
                        Add hotels, resorts, transport partners and other vendors you work with regularly.
                    @endif
                </p>
                @unless (request()->anyFilled(['q', 'type']))
                    <x-button tag="a" href="{{ route('suppliers.create') }}" class="mt-4">+ Add supplier</x-button>
                @endunless
            </div>
        </div>
    @else
        {{-- Desktop rows --}}
        <div class="hidden sm:block bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100">
            @foreach ($suppliers as $supplier)
                <a href="{{ route('suppliers.show', $supplier) }}" class="group flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50/70 transition-colors">
                    <x-avatar :name="$supplier->name" />

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $supplier->name }}</p>
                        <p class="text-xs text-slate-400">{{ $supplier->type?->label() ?? 'Uncategorised' }}</p>
                    </div>

                    <div class="w-40 shrink-0 text-sm text-slate-600 truncate">
                        {{ $supplier->phone ?? '—' }}
                    </div>

                    <div class="w-44 shrink-0 text-sm text-slate-500 truncate">
                        {{ $supplier->email ?? '—' }}
                    </div>

                    <div class="w-24 shrink-0 flex justify-end">
                        <x-status-badge :color="$supplier->is_active ? 'green' : 'slate'" :label="$supplier->is_active ? 'Active' : 'Inactive'" />
                    </div>

                    <span class="shrink-0 flex items-center gap-1 text-xs font-medium text-slate-400 group-hover:text-brand-600 transition-colors">
                        View
                        <svg class="h-3.5 w-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-2">
            @foreach ($suppliers as $supplier)
                <a href="{{ route('suppliers.show', $supplier) }}" class="block bg-white rounded-xl border border-slate-200/80 p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <x-avatar :name="$supplier->name" size="sm" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $supplier->name }}</p>
                                <p class="text-xs text-slate-400">{{ $supplier->type?->label() ?? 'Uncategorised' }}</p>
                            </div>
                        </div>
                        <x-status-badge :color="$supplier->is_active ? 'green' : 'slate'" :label="$supplier->is_active ? 'Active' : 'Inactive'" />
                    </div>

                    @if ($supplier->phone || $supplier->email)
                        <div class="mt-2.5 pt-2.5 border-t border-slate-50 text-xs text-slate-500">
                            {{ collect([$supplier->phone, $supplier->email])->filter()->join(' · ') }}
                        </div>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="mt-4">{{ $suppliers->links() }}</div>
    @endif
@endsection

@extends('layouts.app')

@section('title', 'Money')

@section('content')
    @if (auth()->user()->isSuperAdmin())
        <x-empty-state
            title="Money"
            description="Cross-company financial summaries land here as more companies onboard." />
    @else
        <x-page-header title="Money" subtitle="Who owes you, and who you owe, across active bookings." />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Customers who owe --}}
            <div>
                <div class="flex items-end justify-between mb-2.5">
                    <div>
                        <h3 class="text-[13px] font-semibold text-slate-900">Customers who owe</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Customers owe <x-currency :amount="$customerTotal" size="sm" /></p>
                    </div>
                    <div class="flex items-center gap-1 text-xs">
                        <a href="{{ request()->fullUrlWithQuery(['customer_sort' => 'outstanding', 'customer_page' => null]) }}"
                           class="px-2 py-1 rounded-md font-medium {{ $customerSort === 'outstanding' ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-slate-100' }}">Amount</a>
                        <a href="{{ request()->fullUrlWithQuery(['customer_sort' => 'name', 'customer_page' => null]) }}"
                           class="px-2 py-1 rounded-md font-medium {{ $customerSort === 'name' ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-slate-100' }}">Name</a>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200/70 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                    @forelse ($customerBalances as $row)
                        <a href="{{ route('customers.show', $row->id) }}" class="flex items-center gap-3 px-4 py-3 border-b border-slate-50 last:border-0 hover:bg-slate-50/70">
                            <x-avatar :name="$row->name" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $row->name }}</p>
                            </div>
                            <x-currency :amount="$row->outstanding" tone="warning" size="sm" />
                        </a>
                    @empty
                        <div class="flex items-center gap-3 px-4 py-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-900">All customer payments are up to date</p>
                                <p class="text-xs text-slate-500">No active booking has an outstanding balance.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($customerBalances->hasPages())
                    <div class="mt-3">{{ $customerBalances->onEachSide(1)->links() }}</div>
                @endif
            </div>

            {{-- Suppliers we owe --}}
            <div>
                <div class="flex items-end justify-between mb-2.5">
                    <div>
                        <h3 class="text-[13px] font-semibold text-slate-900">Suppliers we owe</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Suppliers owed <x-currency :amount="$supplierTotal" size="sm" /></p>
                    </div>
                    <div class="flex items-center gap-1 text-xs">
                        <a href="{{ request()->fullUrlWithQuery(['supplier_sort' => 'payable', 'supplier_page' => null]) }}"
                           class="px-2 py-1 rounded-md font-medium {{ $supplierSort === 'payable' ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-slate-100' }}">Amount</a>
                        <a href="{{ request()->fullUrlWithQuery(['supplier_sort' => 'name', 'supplier_page' => null]) }}"
                           class="px-2 py-1 rounded-md font-medium {{ $supplierSort === 'name' ? 'bg-brand-800 text-white' : 'text-slate-500 hover:bg-slate-100' }}">Name</a>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200/70 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                    @forelse ($supplierBalances as $row)
                        <a href="{{ route('suppliers.show', $row->id) }}" class="flex items-center gap-3 px-4 py-3 border-b border-slate-50 last:border-0 hover:bg-slate-50/70">
                            <x-avatar :name="$row->name" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $row->name }}</p>
                            </div>
                            <x-currency :amount="$row->payable" tone="warning" size="sm" />
                        </a>
                    @empty
                        <div class="flex items-center gap-3 px-4 py-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-900">No supplier payments currently due</p>
                                <p class="text-xs text-slate-500">Every supplier with a cost is fully paid.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($supplierBalances->hasPages())
                    <div class="mt-3">{{ $supplierBalances->onEachSide(1)->links() }}</div>
                @endif
            </div>
        </div>
    @endif
@endsection

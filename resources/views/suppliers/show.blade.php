@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
    {{-- Supplier dossier hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 2.25a.75.75 0 000 1.5v16.5h-.75a.75.75 0 000 1.5h16.5a.75.75 0 000-1.5H19.5V3.75a.75.75 0 000-1.5h-15zM9 6a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm-.75 3.75A.75.75 0 019 9h1.5a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM9 12.75a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm3.75-6.75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5h-1.5zM12.75 12a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zM9 16.5a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5H9zm3 .75a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75z" /></svg>

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-avatar :name="$supplier->name" class="ring-2 ring-white/10 !bg-white/10 !text-white shrink-0" />
                <div>
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <x-status-badge :color="$supplier->is_active ? 'green' : 'slate'" :label="$supplier->is_active ? 'Active' : 'Inactive'" class="!bg-white/10 !text-white !ring-white/20" />
                        @if ($supplier->type)
                            <span class="text-xs text-brand-300">{{ $supplier->type->label() }}</span>
                        @endif
                    </div>
                    <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">{{ $supplier->name }}</h2>
                    <p class="mt-1 text-sm text-brand-200">
                        {{ collect([$supplier->phone, $supplier->email])->filter()->join(' · ') ?: 'No contact details yet' }}
                    </p>
                </div>
            </div>
            <x-button tag="a" href="{{ route('suppliers.edit', $supplier) }}" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">Edit</x-button>
        </div>

        <div class="relative mt-5 flex flex-wrap gap-x-8 gap-y-3 border-t border-white/10 pt-4">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Total cost</p>
                <p class="text-base sm:text-xl font-semibold text-white mt-0.5 tabular-nums">{{ number_format($totalCost, 2) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Paid</p>
                <p class="text-base sm:text-xl font-semibold text-emerald-400 mt-0.5 tabular-nums">{{ number_format($totalPaid, 2) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Payable</p>
                <p class="text-base sm:text-xl font-semibold mt-0.5 tabular-nums {{ $totalPayable > 0 ? 'text-accent-400' : 'text-white' }}">{{ number_format($totalPayable, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Services / booking items --}}
            <x-summary-panel title="Services">
                @forelse ($supplier->bookingItems as $item)
                    <div class="py-2.5 border-b border-slate-50 last:border-0 text-sm">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('bookings.show', $item->booking) }}" class="text-slate-900 font-medium hover:underline">{{ $item->description }}</a>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ $item->booking->customer->name }} &middot; {{ $item->booking->trip->destination }}
                                    @if ($item->start_at) &middot; {{ $item->start_at->format('d M Y') }} @endif
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                @if ($item->cost !== null)
                                    <p class="text-slate-900 font-medium tabular-nums">{{ number_format($item->cost, 2) }}</p>
                                    @php $due = $item->amountPayable(); @endphp
                                    @if ($due > 0)
                                        <p class="text-xs text-amber-700">Due {{ number_format($due, 2) }}</p>
                                    @elseif ($due < 0)
                                        <p class="text-xs text-blue-700">Overpaid {{ number_format(abs($due), 2) }}</p>
                                    @else
                                        <p class="text-xs text-green-700">Paid in full</p>
                                    @endif
                                @else
                                    <p class="text-xs text-slate-400">Cost not confirmed</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-1.5">
                            <x-status-badge :color="$item->status->badgeColor()" :label="$item->status->label()" />
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No booking services use this supplier yet.</p>
                @endforelse
            </x-summary-panel>

            {{-- Payment history --}}
            <x-summary-panel title="Payment history">
                @forelse ($recentPayments as $payment)
                    <div class="flex items-start justify-between gap-2 py-2 border-b border-slate-50 last:border-0 text-sm">
                        <div class="min-w-0">
                            <a href="{{ route('bookings.show', $payment->bookingItem->booking) }}" class="hover:underline">
                                <span class="{{ $payment->isVoided() ? 'text-slate-400 line-through' : 'text-slate-900' }}">
                                    {{ $payment->paid_at->format('d M Y') }} &middot; {{ $payment->bookingItem->description }}
                                </span>
                            </a>
                            @if ($payment->method) <p class="text-xs text-slate-400">{{ $payment->method->label() }}</p> @endif
                            @if ($payment->isVoided())
                                <p class="text-xs text-red-500">Voided: {{ $payment->voided_reason }}</p>
                            @endif
                        </div>
                        <span class="{{ $payment->isVoided() ? 'text-slate-400 line-through' : 'text-slate-900 font-medium' }} tabular-nums shrink-0">
                            {{ number_format($payment->amount, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No supplier payments recorded yet.</p>
                @endforelse
            </x-summary-panel>
        </div>

        <div class="space-y-6">
            <x-summary-panel title="Details">
                <dl class="divide-y divide-slate-50">
                    <x-detail-row label="Phone">{{ $supplier->phone ?? '—' }}</x-detail-row>
                    <x-detail-row label="WhatsApp">{{ $supplier->whatsapp ?? '—' }}</x-detail-row>
                    <x-detail-row label="Email">{{ $supplier->email ?? '—' }}</x-detail-row>
                    <x-detail-row label="Payment terms">{{ $supplier->payment_terms ?? '—' }}</x-detail-row>
                </dl>
                @if ($supplier->notes)
                    <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $supplier->notes }}</p>
                @endif
            </x-summary-panel>
        </div>
    </div>
@endsection

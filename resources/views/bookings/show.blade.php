@extends('layouts.app')

@section('title', 'Booking #'.$booking->id)

@section('content')
    {{-- Booking dossier hero --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 px-6 py-6 sm:px-8 sm:py-7 mb-6">
        <svg class="absolute -right-8 -bottom-10 h-48 w-48 text-white/[0.05]" viewBox="0 0 24 24" fill="currentColor"><path d="M8.161 2.58a1.5 1.5 0 011.678 0l4.144 2.796 4.144-2.796a1.5 1.5 0 012.373 1.229v13.564a1.5 1.5 0 01-.878 1.363l-5.639 2.579a1.5 1.5 0 01-1.259 0L8.5 18.084l-4.144 2.796A1.5 1.5 0 012 19.65V6.087a1.5 1.5 0 01.878-1.363l5.283-2.144z" /></svg>

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" class="!bg-white/10 !text-white !ring-white/20" />
                    @if ($booking->isInProgress())
                        <x-status-badge color="amber" label="Trip in progress" />
                    @endif
                    @if ($booking->acceptedQuotationVersion)
                        <a href="{{ route('quotation-versions.show', $booking->acceptedQuotationVersion) }}" class="text-xs text-brand-300 hover:text-white">
                            From Quotation V{{ $booking->acceptedQuotationVersion->version_number }}
                        </a>
                    @endif
                </div>
                <h2 class="text-2xl sm:text-[26px] font-semibold tracking-tight text-white">{{ $booking->trip->destination }}</h2>
                <p class="mt-1 text-sm text-brand-200">
                    Booking #{{ $booking->id }} &middot;
                    <a href="{{ route('customers.show', $booking->customer) }}" class="text-white hover:underline">{{ $booking->customer->name }}</a>
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                    <form method="POST" action="{{ route('bookings.complete', $booking) }}">
                        @csrf
                        <x-button type="submit" size="sm" class="!bg-white/10 !border-white/15 !text-white hover:!bg-white/15">Mark completed</x-button>
                    </form>
                    <details class="relative">
                        <summary class="list-none cursor-pointer rounded-md bg-white/10 border border-white/15 text-red-200 text-[13px] font-medium px-3.5 py-2 hover:bg-red-500/20 hover:text-white">
                            Cancel booking
                        </summary>
                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                              class="absolute right-0 mt-1 w-64 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                            @csrf
                            <input name="cancellation_reason" placeholder="Reason (optional)"
                                   class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                            <button type="submit" class="w-full rounded-md bg-red-600 text-white text-xs font-medium px-3 py-1.5 hover:bg-red-700">
                                Confirm cancellation
                            </button>
                        </form>
                    </details>
                @endif
            </div>
        </div>

        <div class="relative mt-5 grid grid-cols-3 gap-x-3 sm:gap-x-6 border-t border-white/10 pt-4">
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Total</p>
                <p class="text-base sm:text-xl font-semibold text-white mt-0.5 tabular-nums truncate">{{ number_format($booking->totalSellPrice(), 2) }}</p>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Paid</p>
                <p class="text-base sm:text-xl font-semibold text-emerald-400 mt-0.5 tabular-nums truncate">{{ number_format($booking->totalPaid(), 2) }}</p>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-300">Balance</p>
                <p class="text-base sm:text-xl font-semibold mt-0.5 tabular-nums truncate {{ $booking->amountRemaining() > 0 ? 'text-accent-400' : 'text-white' }}">{{ number_format($booking->amountRemaining(), 2) }}</p>
            </div>
        </div>
    </div>

    @if ($booking->status === \App\Enums\BookingStatus::Cancelled && $booking->cancellation_reason)
        <x-alert type="error">Cancelled: {{ $booking->cancellation_reason }}</x-alert>
    @endif

    @if ($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer payment --}}
            <x-summary-panel title="Customer payment">
                <div class="flex flex-wrap gap-2 mb-4">
                    @if ($booking->amountRemaining() > 0)
                        <details class="relative">
                            <summary class="list-none cursor-pointer rounded-md bg-brand-700 text-white text-sm font-medium px-4 py-2 hover:bg-brand-800">
                                Record payment
                            </summary>
                            <form method="POST" action="{{ route('customer-payments.store', $booking) }}"
                                  class="absolute left-0 mt-1 w-72 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                                @csrf
                                <input type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required
                                       class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                <input type="date" name="paid_at" value="{{ now()->toDateString() }}" required
                                       class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                <select name="method" required class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                    <option value="">Method</option>
                                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                                <details class="text-xs">
                                    <summary class="cursor-pointer text-slate-500">+ Reference / notes (optional)</summary>
                                    <div class="mt-2 space-y-2">
                                        <input name="reference" placeholder="Reference (e.g. UPI ID)"
                                               class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                        <input name="notes" placeholder="Notes"
                                               class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                    </div>
                                </details>
                                <button type="submit" class="w-full rounded-md bg-brand-700 text-white text-xs font-medium px-3 py-1.5 hover:bg-brand-800">
                                    Save payment
                                </button>
                            </form>
                        </details>
                    @endif

                    @if ($booking->items->whereNotNull('sell_price')->isNotEmpty())
                        <form method="POST" action="{{ route('invoices.store', $booking) }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                                Create invoice
                            </button>
                        </form>
                    @endif
                </div>

                @if ($booking->payments->isNotEmpty())
                    <p class="text-xs font-medium text-slate-400 mb-2">Payment history</p>
                    @foreach ($booking->payments as $payment)
                        <div class="flex items-start justify-between gap-2 py-1.5 border-b border-slate-50 last:border-0 text-sm">
                            <div class="min-w-0">
                                <span class="{{ $payment->isVoided() ? 'text-slate-400 line-through' : 'text-slate-900' }}">
                                    {{ $payment->paid_at->format('d M Y') }} &middot; {{ $payment->method->label() }}
                                </span>
                                @if ($payment->reference)
                                    <p class="text-xs text-slate-400">Ref: {{ $payment->reference }}</p>
                                @endif
                                @if ($payment->isVoided())
                                    <p class="text-xs text-red-500">Voided: {{ $payment->voided_reason }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="{{ $payment->isVoided() ? 'text-slate-400 line-through' : 'text-slate-900 font-medium' }} tabular-nums">
                                    {{ number_format($payment->amount, 2) }}
                                </span>
                                <a href="{{ route('customer-payments.receipt', $payment) }}" target="_blank" class="text-xs text-slate-400 hover:text-slate-900">Receipt</a>
                                @unless ($payment->isVoided())
                                    <details class="relative">
                                        <summary class="list-none cursor-pointer text-xs text-slate-400 hover:text-red-600">Void</summary>
                                        <form method="POST" action="{{ route('customer-payments.void', $payment) }}"
                                              class="absolute right-0 mt-1 w-56 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                                            @csrf
                                            <input name="reason" placeholder="Reason" required
                                                   class="block w-full rounded-md border-slate-300 shadow-sm text-xs">
                                            <button type="submit" class="w-full rounded-md bg-red-600 text-white text-xs font-medium px-3 py-1.5 hover:bg-red-700">
                                                Confirm void
                                            </button>
                                        </form>
                                    </details>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-slate-400">No payments recorded yet.</p>
                @endif

                @if ($booking->invoices->isNotEmpty())
                    <p class="text-xs font-medium text-slate-400 mt-4 mb-2">Invoices</p>
                    @foreach ($booking->invoices as $invoice)
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="flex justify-between items-center py-1.5 text-sm hover:underline">
                            <span class="{{ $invoice->isCancelled() ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $invoice->invoice_number }}</span>
                            <span class="text-slate-500">{{ number_format($invoice->total, 2) }}</span>
                        </a>
                    @endforeach
                @endif
            </x-summary-panel>

            {{-- Trip Readiness --}}
            <x-summary-panel title="Trip readiness">
                <dl class="divide-y divide-slate-50">
                    @foreach ($booking->readinessRows() as $row)
                        <x-detail-row :label="$row['label']">
                            <span class="{{ $row['ok'] ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $row['ok'] ? '✓' : '⚠' }} {{ $row['state'] }}
                            </span>
                        </x-detail-row>
                    @endforeach
                </dl>
            </x-summary-panel>

            {{-- Quotation reference (if converted from one) --}}
            @if (! $booking->isOperationallyLocked() && $booking->acceptedQuotationVersion && $booking->acceptedQuotationVersion->lineItems->isNotEmpty())
                <x-summary-panel title="From the quotation">
                    <p class="text-xs text-slate-400 -mt-1 mb-3">Convert a line into a service below, or skip it and add services manually.</p>
                    @foreach ($booking->acceptedQuotationVersion->lineItems as $line)
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-50 last:border-0 text-sm">
                            <span class="text-slate-700">{{ $line->description }} &mdash; {{ number_format($line->sell_price, 2) }}</span>
                            <form method="POST" action="{{ route('booking-items.store', $booking) }}">
                                @csrf
                                <input type="hidden" name="description" value="{{ $line->description }}">
                                <input type="hidden" name="category" value="{{ $line->category?->value }}">
                                <input type="hidden" name="sell_price" value="{{ $line->sell_price }}">
                                <button type="submit" class="text-xs rounded-md border border-slate-300 px-2 py-1 text-slate-600 hover:bg-slate-50">
                                    Convert to service
                                </button>
                            </form>
                        </div>
                    @endforeach
                </x-summary-panel>
            @endif

            {{-- Booking items --}}
            <x-summary-panel title="Services">
                @forelse ($booking->items as $item)
                    @include('booking_items._item', ['item' => $item, 'locked' => $booking->isOperationallyLocked()])
                @empty
                    <p class="text-sm text-slate-400">No services added yet.</p>
                @endforelse

                @unless ($booking->isOperationallyLocked())
                    <form method="POST" action="{{ route('booking-items.store', $booking) }}" class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <input type="text" name="description" placeholder="Add a service…" required
                                   class="sm:col-span-2 rounded-md border-slate-300 shadow-sm text-sm">
                            <select name="category" class="rounded-md border-slate-300 shadow-sm text-sm">
                                <option value="">Category (optional)</option>
                                @foreach (\App\Enums\QuotationLineCategory::cases() as $category)
                                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
                            Add service
                        </button>
                    </form>
                @else
                    <p class="text-xs text-slate-400 mt-3 pt-3 border-t border-slate-100">
                        This booking is {{ strtolower($booking->status->label()) }} — services are read-only.
                    </p>
                @endunless
            </x-summary-panel>

            {{-- Supplier costs --}}
            @php
                // Show any item that has a supplier, a cost, OR a payment
                // already recorded against it — an advance can legitimately
                // be paid before either of those is set (see BookingItem::amountPayable()).
                $costedItems = $booking->items
                    ->whereNotNull('supplier_id')
                    ->merge($booking->items->whereNotNull('cost'))
                    ->merge($booking->items->filter(fn ($item) => $item->supplierPayments->isNotEmpty()));
            @endphp
            @if ($costedItems->isNotEmpty())
                <x-summary-panel title="Supplier costs">
                    @foreach ($costedItems->unique('id') as $item)
                        <div class="py-2.5 border-b border-slate-50 last:border-0 text-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-slate-900 font-medium">{{ $item->description }}</span>
                                    @if ($item->supplier_name_snapshot || $item->supplier)
                                        <p class="text-xs text-slate-500">{{ $item->supplier_name_snapshot ?? $item->supplier->name }}</p>
                                    @endif
                                </div>
                                <div class="text-right shrink-0">
                                    @if ($item->cost !== null)
                                        <p class="text-slate-900 font-medium tabular-nums">{{ number_format($item->cost, 2) }}</p>
                                        @php $due = $item->amountPayable(); @endphp
                                        @if ($due > 0)
                                            <p class="text-xs text-amber-700">
                                                Paid {{ number_format($item->totalPaidToSupplier(), 2) }} &middot; Due {{ number_format($due, 2) }}
                                            </p>
                                        @elseif ($due < 0)
                                            <p class="text-xs text-blue-700">
                                                Paid {{ number_format($item->totalPaidToSupplier(), 2) }} &middot; Overpaid by {{ number_format(abs($due), 2) }}
                                            </p>
                                        @else
                                            <p class="text-xs text-green-700">
                                                Paid {{ number_format($item->totalPaidToSupplier(), 2) }} &middot; Due 0.00
                                            </p>
                                        @endif
                                    @else
                                        <p class="text-xs text-slate-400">Cost not confirmed yet</p>
                                        @if ($item->totalPaidToSupplier() > 0)
                                            <p class="text-xs text-slate-500">Paid {{ number_format($item->totalPaidToSupplier(), 2) }}</p>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            @include('supplier_payments._history', ['item' => $item])

                            @if ($item->amountPayable() === null || $item->amountPayable() > 0)
                                <details class="relative mt-2">
                                    <summary class="list-none cursor-pointer text-xs rounded-md border border-slate-300 px-2 py-1 text-slate-600 hover:bg-slate-50 inline-block">
                                        Record payment
                                    </summary>
                                    <form method="POST" action="{{ route('supplier-payments.store', $item) }}"
                                          class="absolute left-0 mt-1 w-64 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                                        @csrf
                                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required
                                               class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                        <input type="date" name="paid_at" value="{{ now()->toDateString() }}" required
                                               class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                        <select name="method" class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                            <option value="">Method (optional)</option>
                                            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                                <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                            @endforeach
                                        </select>
                                        <input name="reference" placeholder="Reference (optional)"
                                               class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                                        <button type="submit" class="w-full rounded-md bg-brand-700 text-white text-xs font-medium px-3 py-1.5 hover:bg-brand-800">
                                            Save payment
                                        </button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    @endforeach
                </x-summary-panel>
            @endif

            {{-- Activity --}}
            @if ($activities->isNotEmpty())
                <x-summary-panel title="Activity">
                    <div class="relative">
                        @foreach ($activities as $activity)
                            <div class="flex gap-3 pb-3 last:pb-0">
                                <div class="flex flex-col items-center shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-400 mt-1.5"></span>
                                    @if (! $loop->last)<span class="w-px flex-1 bg-slate-100 mt-1"></span>@endif
                                </div>
                                <div class="flex-1 min-w-0 flex justify-between gap-2 text-sm">
                                    <span class="text-slate-600">{{ $activity->description }}</span>
                                    <span class="text-xs text-slate-400 shrink-0">{{ $activity->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-summary-panel>
            @endif
        </div>

        <div class="space-y-6">
            <x-summary-panel title="Overview">
                <dl class="divide-y divide-slate-50">
                    <x-detail-row label="Customer"><a href="{{ route('customers.show', $booking->customer) }}" class="hover:underline">{{ $booking->customer->name }}</a></x-detail-row>
                    <x-detail-row label="Trip"><a href="{{ route('trips.show', $booking->trip) }}" class="hover:underline">{{ $booking->trip->destination }}</a></x-detail-row>
                    <x-detail-row label="Dates">
                        {{ $booking->trip->start_date?->format('d M Y') ?? '—' }}
                        @if ($booking->trip->end_date) &ndash; {{ $booking->trip->end_date->format('d M Y') }} @endif
                    </x-detail-row>
                </dl>
            </x-summary-panel>

            <x-summary-panel title="Checklist">
                @foreach (\App\Enums\ChecklistPhase::cases() as $phase)
                    @php $items = $booking->checklistItems->where('phase', $phase); @endphp
                    @if ($items->isNotEmpty())
                        <p class="text-xs font-medium text-slate-400 mt-3 first:mt-0">{{ $phase->label() }}</p>
                        @foreach ($items as $item)
                            @include('booking_checklist._item', ['item' => $item])
                        @endforeach
                    @endif
                @endforeach

                <form method="POST" action="{{ route('booking-checklist-items.store', $booking) }}" class="flex gap-2 mt-3 pt-3 border-t border-slate-100">
                    @csrf
                    <input type="text" name="label" placeholder="Add a checklist item…" required
                           class="flex-1 rounded-md border-slate-300 shadow-sm text-sm">
                    <x-button type="submit" variant="secondary">Add</x-button>
                </form>
            </x-summary-panel>
        </div>
    </div>
@endsection

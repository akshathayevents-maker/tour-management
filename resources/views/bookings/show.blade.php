@extends('layouts.app')

@section('title', 'Booking #'.$booking->id)

@section('content')
    <x-page-header title="Booking #{{ $booking->id }}" subtitle="{{ $booking->trip->destination }} · {{ $booking->customer->name }}">
        <x-slot:actions>
            @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                <form method="POST" action="{{ route('bookings.complete', $booking) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                        Mark completed
                    </button>
                </form>
                <details class="relative">
                    <summary class="list-none cursor-pointer rounded-md bg-white border border-slate-300 text-red-700 text-sm font-medium px-4 py-2 hover:bg-red-50">
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
        </x-slot:actions>
    </x-page-header>

    <div class="flex items-center gap-3 mb-4">
        <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" />
        @if ($booking->isInProgress())
            <x-status-badge color="amber" label="Trip in progress" />
        @endif
        @if ($booking->acceptedQuotationVersion)
            <a href="{{ route('quotation-versions.show', $booking->acceptedQuotationVersion) }}" class="text-xs text-slate-400 hover:underline">
                From Quotation V{{ $booking->acceptedQuotationVersion->version_number }}
            </a>
        @endif
    </div>

    @if ($booking->status === \App\Enums\BookingStatus::Cancelled && $booking->cancellation_reason)
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            Cancelled: {{ $booking->cancellation_reason }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer payment --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Customer payment</h3>

                <div class="grid grid-cols-3 gap-2 text-center mb-4">
                    <div>
                        <p class="text-xs text-slate-500">Total</p>
                        <p class="text-lg font-semibold text-slate-900">{{ number_format($booking->totalSellPrice(), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Paid</p>
                        <p class="text-lg font-semibold text-green-700">{{ number_format($booking->totalPaid(), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Remaining</p>
                        <p class="text-lg font-semibold {{ $booking->amountRemaining() > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                            {{ number_format($booking->amountRemaining(), 2) }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mb-4">
                    @if ($booking->amountRemaining() > 0)
                        <details class="relative">
                            <summary class="list-none cursor-pointer rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
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
                                <button type="submit" class="w-full rounded-md bg-slate-900 text-white text-xs font-medium px-3 py-1.5 hover:bg-slate-700">
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
            </div>

            {{-- Trip Readiness --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Trip readiness</h3>
                <dl class="space-y-1.5 text-sm">
                    @foreach ($booking->readinessRows() as $row)
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-500">{{ $row['label'] }}</dt>
                            <dd class="{{ $row['ok'] ? 'text-green-700' : 'text-amber-700' }} font-medium">
                                {{ $row['ok'] ? '✓' : '⚠' }} {{ $row['state'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Quotation reference (if converted from one) --}}
            @if (! $booking->isOperationallyLocked() && $booking->acceptedQuotationVersion && $booking->acceptedQuotationVersion->lineItems->isNotEmpty())
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <h3 class="text-sm font-medium text-slate-900 mb-1">From the quotation</h3>
                    <p class="text-xs text-slate-400 mb-3">Convert a line into a service below, or skip it and add services manually.</p>
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
                </div>
            @endif

            {{-- Booking items --}}
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Services</h3>

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
            </div>

            {{-- Activity --}}
            @if ($activities->isNotEmpty())
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <h3 class="text-sm font-medium text-slate-900 mb-3">Activity</h3>
                    @foreach ($activities as $activity)
                        <div class="flex justify-between text-sm py-1 border-b border-slate-50 last:border-0">
                            <span class="text-slate-600">{{ $activity->description }}</span>
                            <span class="text-xs text-slate-400">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Overview</h3>
                <dl class="grid grid-cols-2 gap-y-2 text-sm">
                    <dt class="text-slate-500">Customer</dt>
                    <dd class="text-slate-900"><a href="{{ route('customers.show', $booking->customer) }}" class="hover:underline">{{ $booking->customer->name }}</a></dd>
                    <dt class="text-slate-500">Trip</dt>
                    <dd class="text-slate-900"><a href="{{ route('trips.show', $booking->trip) }}" class="hover:underline">{{ $booking->trip->destination }}</a></dd>
                    <dt class="text-slate-500">Dates</dt>
                    <dd class="text-slate-900">
                        {{ $booking->trip->start_date?->format('d M Y') ?? '—' }}
                        @if ($booking->trip->end_date) &ndash; {{ $booking->trip->end_date->format('d M Y') }} @endif
                    </dd>
                </dl>
            </div>

            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <h3 class="text-sm font-medium text-slate-900 mb-3">Checklist</h3>

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
                    <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
                        Add
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('content')
    <x-page-header :title="$invoice->invoice_number" subtitle="{{ $invoice->booking->trip->destination }} · {{ $invoice->booking->customer->name }}">
        <x-slot:actions>
            <a href="{{ route('bookings.show', $invoice->booking) }}"
               class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                Back to booking
            </a>
            @unless ($invoice->isCancelled())
                <details class="relative">
                    <summary class="list-none cursor-pointer rounded-md bg-white border border-slate-300 text-red-700 text-sm font-medium px-4 py-2 hover:bg-red-50">
                        Cancel invoice
                    </summary>
                    <form method="POST" action="{{ route('invoices.cancel', $invoice) }}"
                          class="absolute right-0 mt-1 w-64 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                        @csrf
                        <input name="cancellation_reason" placeholder="Reason (optional)"
                               class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                        <button type="submit" class="w-full rounded-md bg-red-600 text-white text-xs font-medium px-3 py-1.5 hover:bg-red-700">
                            Confirm cancellation
                        </button>
                    </form>
                </details>
            @endunless
        </x-slot:actions>
    </x-page-header>

    <div class="flex items-center gap-3 mb-4">
        <x-status-badge :color="$invoice->status->badgeColor()" :label="$invoice->status->label()" />
    </div>

    @if ($invoice->isCancelled() && $invoice->cancellation_reason)
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
            Cancelled: {{ $invoice->cancellation_reason }}
        </div>
    @endif

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <p class="font-semibold text-slate-900">{{ $invoice->booking->company->name }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-400">Invoice</p>
                <p class="text-sm text-slate-900">{{ $invoice->issued_at->format('d M Y') }}</p>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-y-2 text-sm mb-6">
            <dt class="text-slate-500">Billed to</dt>
            <dd class="text-slate-900">{{ $invoice->customer_name_snapshot }}</dd>
            <dt class="text-slate-500">Trip</dt>
            <dd class="text-slate-900">{{ $invoice->booking->trip->destination }}</dd>
        </dl>

        <div class="border-t border-slate-100 pt-3 space-y-1">
            @foreach ($invoice->lineItems as $line)
                <div class="flex justify-between text-sm">
                    <span class="text-slate-700">{{ $line->description }}</span>
                    <span class="text-slate-900 tabular-nums">{{ number_format($line->amount, 2) }}</span>
                </div>
            @endforeach
        </div>

        <div class="border-t border-slate-200 mt-3 pt-3 space-y-1 text-sm">
            <div class="flex justify-between text-slate-500">
                <span>Subtotal</span>
                <span>{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if ($invoice->tax_amount !== null)
                <div class="flex justify-between text-slate-500">
                    <span>Tax ({{ number_format($invoice->tax_rate, 2) }}%)</span>
                    <span>{{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between text-slate-900 font-semibold text-base pt-1">
                <span>Total</span>
                <span>{{ number_format($invoice->total, 2) }}</span>
            </div>
        </div>
    </div>
@endsection

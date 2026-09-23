@extends('layouts.app')

@section('title', $invoice->invoice_number)

@section('content')
    <x-page-header :title="$invoice->invoice_number" subtitle="{{ $invoice->booking->trip->destination }} · {{ $invoice->booking->customer->name }}">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('bookings.show', $invoice->booking) }}" variant="secondary">Back to booking</x-button>
            @unless ($invoice->isCancelled())
                <details class="relative">
                    <summary class="list-none cursor-pointer rounded-md bg-white border border-slate-300 text-red-700 text-sm font-medium px-3.5 py-2 hover:bg-red-50 shadow-sm">
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

    <div class="mb-5">
        <x-status-badge :color="$invoice->status->badgeColor()" :label="$invoice->status->label()" />
    </div>

    @if ($invoice->isCancelled() && $invoice->cancellation_reason)
        <x-alert type="error">Cancelled: {{ $invoice->cancellation_reason }}</x-alert>
    @endif

    <div class="max-w-xl bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex justify-between items-start mb-6 pb-4 border-b border-slate-100">
            <p class="font-semibold text-slate-900">{{ $invoice->booking->company->name }}</p>
            <div class="text-right">
                <p class="text-xs text-slate-400">Invoice</p>
                <p class="text-sm text-slate-900">{{ $invoice->issued_at->format('d M Y') }}</p>
            </div>
        </div>

        <dl class="divide-y divide-slate-50 mb-4">
            <x-detail-row label="Billed to">{{ $invoice->customer_name_snapshot }}</x-detail-row>
            <x-detail-row label="Trip">{{ $invoice->booking->trip->destination }}</x-detail-row>
        </dl>

        <div class="border-t border-slate-100 pt-3 space-y-1.5">
            @foreach ($invoice->lineItems as $line)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-700">{{ $line->description }}</span>
                    <span class="text-slate-900 tabular-nums">{{ number_format($line->amount, 2) }}</span>
                </div>
            @endforeach
        </div>

        <div class="border-t border-slate-200 mt-3 pt-3 space-y-1.5 text-sm">
            <div class="flex justify-between items-center text-slate-500">
                <span>Subtotal</span>
                <span class="tabular-nums">{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if ($invoice->tax_amount !== null)
                <div class="flex justify-between items-center text-slate-500">
                    <span>Tax ({{ number_format($invoice->tax_rate, 2) }}%)</span>
                    <span class="tabular-nums">{{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between items-center pt-1.5 border-t border-slate-100">
                <span class="text-slate-900 font-medium">Total</span>
                <x-currency :amount="$invoice->total" size="lg" />
            </div>
        </div>
    </div>
@endsection

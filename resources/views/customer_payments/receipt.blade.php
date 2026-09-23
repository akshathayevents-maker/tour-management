@extends('layouts.app')

@section('title', 'Receipt')

@section('content')
    <x-page-header title="Payment receipt" subtitle="{{ $customerPayment->booking->trip->destination }} · {{ $customerPayment->booking->customer->name }}">
        <x-slot:actions>
            <a href="{{ route('bookings.show', $customerPayment->booking) }}"
               class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-4 py-2 hover:bg-slate-50">
                Back to booking
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <p class="font-semibold text-slate-900">{{ $customerPayment->booking->company->name }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-400">Receipt</p>
                <p class="text-sm text-slate-900">{{ $customerPayment->paid_at->format('d M Y') }}</p>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-y-2 text-sm mb-6">
            <dt class="text-slate-500">Received from</dt>
            <dd class="text-slate-900">{{ $customerPayment->booking->customer->name }}</dd>
            <dt class="text-slate-500">Trip</dt>
            <dd class="text-slate-900">{{ $customerPayment->booking->trip->destination }}</dd>
            <dt class="text-slate-500">Method</dt>
            <dd class="text-slate-900">{{ $customerPayment->method->label() }}</dd>
            @if ($customerPayment->reference)
                <dt class="text-slate-500">Reference</dt>
                <dd class="text-slate-900">{{ $customerPayment->reference }}</dd>
            @endif
        </dl>

        <div class="border-t border-slate-200 pt-4 flex justify-between items-center">
            <span class="text-slate-500">Amount received</span>
            <span class="text-xl font-semibold text-slate-900">{{ number_format($customerPayment->amount, 2) }}</span>
        </div>

        @if ($customerPayment->isVoided())
            <div class="mt-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                This payment was voided: {{ $customerPayment->voided_reason }}
            </div>
        @endif

        @if ($customerPayment->notes)
            <p class="mt-4 text-xs text-slate-400 border-t border-slate-100 pt-3">{{ $customerPayment->notes }}</p>
        @endif
    </div>
@endsection

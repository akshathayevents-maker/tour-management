<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking #{{ $booking->id }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-page { box-shadow: none !important; margin: 0 !important; border: none !important; }
        }
        @page {
            size: A4;
            margin: 16mm 14mm;
        }
        body {
            background: #f1f0ec;
        }
        .print-page {
            max-width: 210mm;
            margin: 24px auto;
            background: #fff;
            border: 1px solid #e2e8f0;
        }
        table { border-collapse: collapse; width: 100%; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body class="text-slate-900">
    <div class="no-print sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
        <a href="{{ route('bookings.show', $booking) }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Back to booking</a>
        <button type="button" onclick="window.print()" class="rounded-md bg-brand-700 text-white text-sm font-semibold px-4 py-2 hover:bg-brand-800">Print</button>
    </div>

    <div class="print-page px-10 py-10 sm:px-14 sm:py-12">
        {{-- Business header --}}
        <div class="flex items-start justify-between border-b border-slate-200 pb-6 mb-6">
            <div>
                <p class="text-lg font-semibold tracking-tight text-slate-900">{{ $booking->company->name }}</p>
                @if ($booking->company->email || $booking->company->phone)
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ collect([$booking->company->email, $booking->company->phone])->filter()->join(' · ') }}
                    </p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Booking confirmation</p>
                <p class="text-lg font-semibold text-slate-900">Booking #{{ $booking->id }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Issued {{ $booking->created_at->format('d M Y') }}</p>
            </div>
        </div>

        {{-- Customer / booking info --}}
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Customer</p>
                <p class="text-sm font-medium text-slate-900">{{ $booking->customer->name }}</p>
                <p class="text-xs text-slate-500">{{ $booking->customer->phone }}</p>
                @if ($booking->customer->email)
                    <p class="text-xs text-slate-500">{{ $booking->customer->email }}</p>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Booking details</p>
                <dl class="text-xs text-slate-600 space-y-1">
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Destination</dt><dd class="font-medium text-slate-900">{{ $booking->trip->destination }}</dd></div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-400">Travel dates</dt>
                        <dd class="font-medium text-slate-900">
                            @if ($booking->trip->start_date)
                                {{ $booking->trip->start_date->format('d M Y') }}@if ($booking->trip->end_date) &ndash; {{ $booking->trip->end_date->format('d M Y') }}@endif
                            @else
                                Not scheduled
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Status</dt><dd class="font-medium text-slate-900">{{ $booking->status->label() }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- Items --}}
        <table class="text-sm mb-6">
            <thead>
                <tr class="border-b-2 border-slate-300 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <th class="py-2 pr-3">Service</th>
                    <th class="py-2 pr-3">Category</th>
                    <th class="py-2 pr-3">Supplier</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($booking->items as $item)
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 pr-3 font-medium text-slate-900">{{ $item->description }}</td>
                        <td class="py-2.5 pr-3 text-slate-600">{{ $item->category?->label() ?? '—' }}</td>
                        <td class="py-2.5 pr-3 text-slate-600">{{ $item->supplier?->name ?? $item->supplier_name_snapshot ?? '—' }}</td>
                        <td class="py-2.5 pr-3 text-slate-600">{{ $item->status->label() }}</td>
                        <td class="py-2.5 text-right tabular-nums text-slate-900">{{ $item->sell_price !== null ? number_format((float) $item->sell_price, 2) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-center text-slate-400">No services added yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Financial summary --}}
        <div class="flex justify-end">
            <dl class="w-64 text-sm space-y-1.5">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Subtotal</dt>
                    <dd class="font-medium text-slate-900 tabular-nums">{{ number_format($booking->totalSellPrice(), 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Amount paid</dt>
                    <dd class="font-medium text-emerald-700 tabular-nums">{{ number_format($booking->totalPaid(), 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-1.5 mt-1.5">
                    <dt class="font-semibold text-slate-900">Amount remaining</dt>
                    <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($booking->amountRemaining(), 2) }}</dd>
                </div>
            </dl>
        </div>

        <p class="mt-10 pt-6 border-t border-slate-100 text-[11px] text-slate-400">
            This document was generated from {{ $booking->company->name }}'s booking records on {{ now()->format('d M Y') }}.
        </p>
    </div>
</body>
</html>

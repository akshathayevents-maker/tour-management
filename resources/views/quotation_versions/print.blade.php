<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation V{{ $quotationVersion->version_number }} · {{ config('app.name') }}</title>
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
    @php
        $quotation = $quotationVersion->quotation;
        $trip = $quotation->trip;
        $customer = $trip->customer;
        $company = $quotation->company;
    @endphp

    <div class="no-print sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
        <a href="{{ route('quotation-versions.show', $quotationVersion) }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Back to quotation</a>
        <button type="button" onclick="window.print()" class="rounded-md bg-brand-700 text-white text-sm font-semibold px-4 py-2 hover:bg-brand-800">Print</button>
    </div>

    <div class="print-page px-10 py-10 sm:px-14 sm:py-12">
        {{-- Business header --}}
        <div class="flex items-start justify-between border-b border-slate-200 pb-6 mb-6">
            <div>
                <p class="text-lg font-semibold tracking-tight text-slate-900">{{ $company->name }}</p>
                @if ($company->email || $company->phone)
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ collect([$company->email, $company->phone])->filter()->join(' · ') }}
                    </p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Quotation</p>
                <p class="text-lg font-semibold text-slate-900">Quotation V{{ $quotationVersion->version_number }}</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ $quotationVersion->isExpired() ? 'Expired' : $quotationVersion->status->label() }}
                    @if ($quotationVersion->sent_at) &middot; Sent {{ $quotationVersion->sent_at->format('d M Y') }} @endif
                </p>
                @if ($quotationVersion->valid_until)
                    <p class="text-xs text-slate-500">Valid until {{ $quotationVersion->valid_until->format('d M Y') }}</p>
                @endif
            </div>
        </div>

        {{-- Customer / travel info --}}
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Customer</p>
                <p class="text-sm font-medium text-slate-900">{{ $customer->name }}</p>
                <p class="text-xs text-slate-500">{{ $customer->phone }}</p>
                @if ($customer->email)
                    <p class="text-xs text-slate-500">{{ $customer->email }}</p>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Travel details</p>
                <dl class="text-xs text-slate-600 space-y-1">
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Destination</dt><dd class="font-medium text-slate-900">{{ $trip->destination }}</dd></div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-400">Travel dates</dt>
                        <dd class="font-medium text-slate-900">
                            @if ($trip->start_date)
                                {{ $trip->start_date->format('d M Y') }}@if ($trip->end_date) &ndash; {{ $trip->end_date->format('d M Y') }}@endif
                            @else
                                Not scheduled
                            @endif
                        </dd>
                    </div>
                    @if ($trip->travellers_count)
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Travellers</dt><dd class="font-medium text-slate-900">{{ $trip->travellers_count }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Line items --}}
        <table class="text-sm mb-6">
            <thead>
                <tr class="border-b-2 border-slate-300 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <th class="py-2 pr-3">Description</th>
                    <th class="py-2 pr-3">Category</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quotationVersion->lineItems as $item)
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 pr-3 font-medium text-slate-900">{{ $item->description }}</td>
                        <td class="py-2.5 pr-3 text-slate-600">{{ $item->category?->label() ?? '—' }}</td>
                        <td class="py-2.5 text-right tabular-nums text-slate-900">{{ number_format((float) $item->sell_price, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-slate-400">No line items added yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Financial summary --}}
        <div class="flex justify-end">
            <dl class="w-64 text-sm space-y-1.5">
                <div class="flex justify-between border-t border-slate-200 pt-1.5">
                    <dt class="font-semibold text-slate-900">Total</dt>
                    <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($quotationVersion->totalSellPrice(), 2) }}</dd>
                </div>
            </dl>
        </div>

        {{-- Terms / notes --}}
        @if ($quotationVersion->terms || $quotationVersion->notes)
            <div class="mt-8 pt-6 border-t border-slate-100 space-y-4">
                @if ($quotationVersion->terms)
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Terms</p>
                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ $quotationVersion->terms }}</p>
                    </div>
                @endif
                @if ($quotationVersion->notes)
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Notes</p>
                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ $quotationVersion->notes }}</p>
                    </div>
                @endif
            </div>
        @endif

        <p class="mt-10 pt-6 border-t border-slate-100 text-[11px] text-slate-400">
            This quotation was generated by {{ $company->name }} on {{ now()->format('d M Y') }}.
        </p>
    </div>
</body>
</html>

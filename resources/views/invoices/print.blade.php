<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }} · {{ config('app.name') }}</title>
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
    @php $company = $invoice->booking->company; @endphp

    <div class="no-print sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
        <a href="{{ route('invoices.show', $invoice) }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Back to invoice</a>
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
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Invoice</p>
                <p class="text-lg font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                <p class="text-xs text-slate-500 mt-0.5">Issued {{ $invoice->issued_at->format('d M Y') }}</p>
                <p class="text-xs text-slate-500">{{ $invoice->status->label() }}</p>
            </div>
        </div>

        @if ($invoice->isCancelled())
            <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">
                This invoice has been cancelled.
                @if ($invoice->cancellation_reason)
                    {{ $invoice->cancellation_reason }}
                @endif
            </div>
        @endif

        {{-- Billed to / reference --}}
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Billed to</p>
                <p class="text-sm font-medium text-slate-900">{{ $invoice->customer_name_snapshot }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Reference</p>
                <dl class="text-xs text-slate-600 space-y-1">
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Booking</dt><dd class="font-medium text-slate-900">#{{ $invoice->booking_id }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Trip</dt><dd class="font-medium text-slate-900">{{ $invoice->booking->trip->destination }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- Line items --}}
        <table class="text-sm mb-6">
            <thead>
                <tr class="border-b-2 border-slate-300 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <th class="py-2 pr-3">Description</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->lineItems as $line)
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5 pr-3 font-medium text-slate-900">{{ $line->description }}</td>
                        <td class="py-2.5 text-right tabular-nums text-slate-900">{{ number_format((float) $line->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Financial summary --}}
        <div class="flex justify-end">
            <dl class="w-64 text-sm space-y-1.5">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Subtotal</dt>
                    <dd class="font-medium text-slate-900 tabular-nums">{{ number_format((float) $invoice->subtotal, 2) }}</dd>
                </div>
                @if ($invoice->tax_amount !== null)
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Tax ({{ number_format((float) $invoice->tax_rate, 2) }}%)</dt>
                        <dd class="font-medium text-slate-900 tabular-nums">{{ number_format((float) $invoice->tax_amount, 2) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between border-t border-slate-200 pt-1.5 mt-1.5">
                    <dt class="font-semibold text-slate-900">Total</dt>
                    <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format((float) $invoice->total, 2) }}</dd>
                </div>
            </dl>
        </div>

        <p class="mt-10 pt-6 border-t border-slate-100 text-[11px] text-slate-400">
            This invoice was generated by {{ $company->name }} on {{ now()->format('d M Y') }}.
        </p>
    </div>
</body>
</html>

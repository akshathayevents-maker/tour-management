<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Itinerary · {{ $trip->destination }} · {{ config('app.name') }}</title>
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
        .day-block { page-break-inside: avoid; }
    </style>
</head>
<body class="text-slate-900">
    @php $customer = $trip->customer; $company = $trip->company; @endphp

    <div class="no-print sticky top-0 z-10 bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
        <a href="{{ route('trips.show', $trip) }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Back to trip</a>
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
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Travel itinerary</p>
                <p class="text-lg font-semibold text-slate-900">Trip #{{ $trip->id }}</p>
            </div>
        </div>

        {{-- Customer / trip info --}}
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
                <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5">Trip details</p>
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

        {{-- Itinerary --}}
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-4">Itinerary</p>

            @if (! $trip->itinerary || $trip->itinerary->days->isEmpty())
                <p class="text-sm text-slate-400">No itinerary has been planned for this trip yet.</p>
            @else
                <div class="space-y-6">
                    @foreach ($trip->itinerary->days as $day)
                        <div class="day-block border-b border-slate-100 pb-5 last:border-0">
                            <div class="flex items-baseline gap-2 mb-1">
                                <p class="text-sm font-semibold text-slate-900">Day {{ str_pad($day->day_number, 2, '0', STR_PAD_LEFT) }}</p>
                                @if ($day->date)
                                    <p class="text-xs text-slate-400">{{ $day->date->format('d M Y') }}</p>
                                @endif
                            </div>

                            @if ($day->notes)
                                <p class="text-sm text-slate-600 leading-relaxed mb-2">{{ $day->notes }}</p>
                            @endif

                            @if ($day->items->isNotEmpty())
                                <ul class="space-y-1.5 mt-2">
                                    @foreach ($day->items as $item)
                                        <li class="flex items-start gap-2.5 text-sm">
                                            @if ($item->time)
                                                <span class="shrink-0 w-14 text-xs text-slate-400 tabular-nums pt-0.5">{{ \Carbon\Carbon::parse($item->time)->format('g:i A') }}</span>
                                            @endif
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $item->title }}</p>
                                                @if ($item->notes)
                                                    <p class="text-xs text-slate-500">{{ $item->notes }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <p class="mt-10 pt-6 border-t border-slate-100 text-[11px] text-slate-400">
            This itinerary was generated by {{ $company->name }} on {{ now()->format('d M Y') }}.
        </p>
    </div>
</body>
</html>

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if (auth()->user()->isSuperAdmin())
        <div class="dash">
            <div class="dash-card px-6 py-10 text-center">
                <p class="text-lg font-semibold" style="color: var(--dash-text)">Welcome, {{ auth()->user()->name }}</p>
                <p class="mt-1 text-sm" style="color: var(--dash-text-2)">Platform-wide metrics land here as more companies onboard.</p>
            </div>
        </div>
    @else
        @php
            $attentionItems = $overdueFollowUps->map(fn ($f) => ['followUp' => $f, 'overdue' => true])
                ->concat($todayFollowUps->map(fn ($f) => ['followUp' => $f, 'overdue' => false]));

            $overdueCount = $overdueFollowUps->count();
            $todayCount = $todayFollowUps->count();
            $followUpTotal = max(1, $overdueCount + $todayCount);
            $weeklyPaymentsTotal = array_sum($paymentsTrend);
        @endphp

        <div class="dash">
            {{-- Hero — compact command-center header, not a tall banner --}}
            <div class="relative overflow-hidden rounded-xl px-5 py-4 sm:px-6 sm:py-5 mb-4"
                 style="background: radial-gradient(circle at 8% -30%, rgba(56,189,248,.12), transparent 40%), radial-gradient(circle at 105% 140%, rgba(139,92,246,.1), transparent 45%), linear-gradient(135deg, #0a1120, #0b1220 55%, #0c1526); border: 1px solid var(--dash-border);">
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(var(--dash-text) 1px, transparent 1px), linear-gradient(90deg, var(--dash-text) 1px, transparent 1px); background-size: 30px 30px;"></div>
                <div class="relative flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest mb-1 flex items-center gap-1.5" style="color: var(--dash-leads)">
                            <span class="h-1.5 w-1.5 rounded-full" style="background: var(--dash-leads); box-shadow: 0 0 6px var(--dash-leads);"></span>
                            {{ now()->format('l, j F Y · g:i A') }}
                        </p>
                        <h2 class="text-xl sm:text-2xl font-semibold tracking-tight" style="color: var(--dash-text)">
                            {{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }}, {{ explode(' ', auth()->user()->name)[0] }}
                        </h2>
                        <p class="mt-0.5 text-xs sm:text-sm" style="color: var(--dash-text-2)">Here's what needs your attention across the business today.</p>
                    </div>
                    @hasanyrole('company_admin|company_user')
                        <div class="flex items-center gap-2">
                            <a href="{{ route('leads.create') }}" class="rounded-md text-xs font-medium px-3 py-1.5 transition-all hover:-translate-y-px" style="background: rgba(34,211,238,.08); border: 1px solid rgba(34,211,238,.3); color: var(--dash-leads)">+ Lead</a>
                            <a href="{{ route('enquiries.create') }}" class="rounded-md text-xs font-medium px-3 py-1.5 transition-all hover:-translate-y-px" style="background: rgba(167,139,250,.08); border: 1px solid rgba(167,139,250,.3); color: var(--dash-enquiries)">+ Enquiry</a>
                            <a href="{{ route('bookings.create') }}" class="rounded-md text-xs font-semibold px-3 py-1.5 transition-all hover:-translate-y-px" style="background: var(--dash-bookings); color: #1a0f02; box-shadow: 0 0 0 1px rgba(251,146,60,.4), 0 4px 16px -4px rgba(251,146,60,.5);">+ Booking</a>
                        </div>
                    @endhasanyrole
                </div>
            </div>

            {{-- Key metrics — four equal-weight KPI cards, same family --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
                {{-- New leads --}}
                <div class="dash-card p-4 flex flex-col justify-between transition-all hover:-translate-y-0.5" style="height: 168px;">
                    <div class="flex items-start justify-between">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(34,211,238,.12); color: var(--dash-leads)">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 00-6 6c0 4.5 6 10 6 10s6-5.5 6-10a6 6 0 00-6-6zm0 8a2 2 0 110-4 2 2 0 010 4z" /></svg>
                        </span>
                    </div>
                    <div>
                        <p class="text-[10.5px] font-semibold uppercase tracking-wide" style="color: var(--dash-text-3)">New leads</p>
                        <p class="mt-1 text-[28px] leading-none font-bold tabular-nums" style="color: var(--dash-text)">{{ $newLeadsCount }}</p>
                        <p class="mt-1 text-[11px]" style="color: var(--dash-text-3)">{{ $newLeadsCount > 0 ? 'Last 7 days' : 'No new leads in the last 7 days' }}</p>
                    </div>
                    <div style="height: 32px">
                        <x-dash.sparkline :values="$leadsTrend" color="#22d3ee" :height="32" />
                    </div>
                </div>

                {{-- New enquiries: donut + status breakdown --}}
                <div class="dash-card p-4 flex flex-col justify-between transition-all hover:-translate-y-0.5" style="height: 168px;">
                    <div class="flex items-start justify-between">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(167,139,250,.12); color: var(--dash-enquiries)">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                        </span>
                        <span class="text-[11px]" style="color: var(--dash-text-3)">7 days</span>
                    </div>
                    <p class="text-[10.5px] font-semibold uppercase tracking-wide" style="color: var(--dash-text-3)">New enquiries</p>
                    <div class="flex items-center gap-3">
                        <x-dash.donut
                            :segments="[
                                ['value' => $enquiryStatusBreakdown['new'] ?? 0, 'color' => '#22d3ee'],
                                ['value' => $enquiryStatusBreakdown['planning'] ?? 0, 'color' => '#f59e0b'],
                                ['value' => $enquiryStatusBreakdown['confirmed'] ?? 0, 'color' => '#10b981'],
                                ['value' => ($enquiryStatusBreakdown['lost'] ?? 0) + ($enquiryStatusBreakdown['cancelled'] ?? 0), 'color' => '#64748b'],
                            ]"
                            :center-value="$newEnquiriesCount"
                            center-label="" />
                        <div class="min-w-0 flex-1 space-y-0.5 text-[10.5px]" style="color: var(--dash-text-2)">
                            <p class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full shrink-0" style="background:#22d3ee"></span>New {{ $enquiryStatusBreakdown['new'] ?? 0 }}</p>
                            <p class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full shrink-0" style="background:#f59e0b"></span>Planning {{ $enquiryStatusBreakdown['planning'] ?? 0 }}</p>
                            <p class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full shrink-0" style="background:#10b981"></span>Confirmed {{ $enquiryStatusBreakdown['confirmed'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                {{-- Overdue follow-ups --}}
                <div class="dash-card p-4 flex flex-col justify-between transition-all hover:-translate-y-0.5" style="height: 168px;">
                    <div class="flex items-start justify-between">
                        <span class="h-2 w-2 rounded-full" style="background: {{ $overdueCount > 0 ? 'var(--dash-danger)' : 'var(--dash-success)' }};"></span>
                    </div>
                    <div>
                        <p class="text-[10.5px] font-semibold uppercase tracking-wide" style="color: var(--dash-text-3)">Overdue follow-ups</p>
                        <p class="mt-1 text-[28px] leading-none font-bold tabular-nums" style="color: var(--dash-text)">{{ $overdueCount }}</p>
                        <p class="mt-1 text-[11px] font-medium" style="color: {{ $overdueCount > 0 ? 'var(--dash-danger)' : 'var(--dash-success)' }}">{{ $overdueCount > 0 ? 'Needs action' : 'All caught up' }}</p>
                    </div>
                    <div class="flex items-end gap-1" style="height: 20px">
                        <div class="flex-1 rounded-t-sm" style="height: {{ max(15, $overdueCount / $followUpTotal * 100) }}%; background: var(--dash-danger);"></div>
                        <div class="flex-1 rounded-t-sm" style="height: {{ max(15, $todayCount / $followUpTotal * 100) }}%; background: var(--dash-warning);"></div>
                    </div>
                </div>

                {{-- Payments today --}}
                <div class="dash-card p-4 flex flex-col justify-between transition-all hover:-translate-y-0.5" style="height: 168px;">
                    <div class="flex items-start justify-between">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(16,185,129,.12); color: var(--dash-success)">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12 1a1 1 0 00-1-1 1 1 0 00-1 1v.092a4.535 4.535 0 00-1.676.662C7.602 2.482 7 3.436 7 4.5c0 1.064.602 2.018 1.324 2.746.17.17.353.333.546.479v2.98a2.44 2.44 0 01-.966-.584 1 1 0 10-1.449 1.378A4.44 4.44 0 008.87 12.9V13.9a1 1 0 102 0v-.012c.709-.082 1.4-.33 1.983-.738.785-.551 1.397-1.397 1.397-2.413 0-1.016-.612-1.862-1.397-2.413a4.17 4.17 0 00-1.983-.738V4.65c.311.1.597.247.835.433a1 1 0 101.238-1.571A4.535 4.535 0 0012 1z" clip-rule="evenodd" /></svg>
                        </span>
                    </div>
                    <div>
                        <p class="text-[10.5px] font-semibold uppercase tracking-wide" style="color: var(--dash-text-3)">Payments today</p>
                        <p class="mt-1 text-[28px] leading-none font-bold tabular-nums" style="color: var(--dash-text)">{{ number_format($todayCustomerPaymentTotal, 0) }}</p>
                        <p class="mt-1 text-[11px]" style="color: var(--dash-text-3)">{{ $todayCustomerPaymentTotal > 0 ? 'Received today' : 'No payments received today' }}</p>
                    </div>
                    <div style="height: 32px">
                        <x-dash.sparkline :values="$paymentsTrend" color="#10b981" :height="32" />
                    </div>
                </div>
            </div>

            {{-- Operations --}}
            <div class="flex items-center justify-between mb-2.5">
                <h3 class="text-[13px] font-semibold" style="color: var(--dash-text)">Operations</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 mb-6">
                {{-- Bookings needing attention --}}
                <div class="dash-card overflow-hidden" style="border-top: 2px solid {{ $bookingsNeedingAttention->isNotEmpty() ? 'var(--dash-bookings)' : 'var(--dash-success)' }};">
                    <div class="flex items-center justify-between px-3.5 pt-3 pb-2">
                        <h3 class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--dash-text-3)">Bookings needing attention</h3>
                        <a href="{{ route('bookings.index') }}" class="text-[11px] font-medium" style="color: var(--dash-primary)">All</a>
                    </div>
                    @forelse ($bookingsNeedingAttention as $booking)
                        <a href="{{ route('bookings.show', $booking) }}" class="dash-row flex items-center gap-2 px-3.5 py-2 border-t" style="border-color: var(--dash-border)">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background: var(--dash-bookings)"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium truncate" style="color: var(--dash-text)">{{ $booking->customer->name }}</p>
                                <p class="text-[10px] truncate" style="color: var(--dash-text-3)">{{ $booking->trip->destination }}</p>
                            </div>
                            <p class="shrink-0 text-xs font-semibold tabular-nums" style="color: var(--dash-bookings)">{{ number_format($booking->amountRemaining(), 0) }}</p>
                        </a>
                    @empty
                        <div class="flex items-center gap-2 px-3.5 py-4 border-t" style="border-color: var(--dash-border)">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" style="background: rgba(16,185,129,.12); color: var(--dash-success)">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </span>
                            <p class="text-xs font-medium" style="color: var(--dash-text)">No outstanding balances</p>
                        </div>
                    @endforelse
                </div>

                {{-- Unconfirmed supplier items --}}
                <div class="dash-card overflow-hidden" style="border-top: 2px solid {{ $unconfirmedSupplierItems->isNotEmpty() ? 'var(--dash-supplier)' : 'var(--dash-success)' }};">
                    <div class="flex items-center justify-between px-3.5 pt-3 pb-2">
                        <h3 class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--dash-text-3)">Unconfirmed supplier items</h3>
                    </div>
                    @forelse ($unconfirmedSupplierItems as $item)
                        <a href="{{ route('bookings.show', $item->booking) }}" class="dash-row flex items-center gap-2 px-3.5 py-2 border-t" style="border-color: var(--dash-border)">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background: {{ $item->status->badgeColor() === 'slate' ? 'var(--dash-text-3)' : 'var(--dash-supplier)' }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium truncate" style="color: var(--dash-text)">{{ $item->description }}</p>
                                <p class="text-[10px] truncate" style="color: var(--dash-text-3)">{{ $item->supplier?->name ?? $item->supplier_name_snapshot ?? 'No supplier' }}</p>
                            </div>
                            <x-status-badge :color="$item->status->badgeColor()" :label="$item->status->label()" />
                        </a>
                    @empty
                        <div class="flex items-center gap-2 px-3.5 py-4 border-t" style="border-color: var(--dash-border)">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" style="background: rgba(16,185,129,.12); color: var(--dash-success)">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </span>
                            <p class="text-xs font-medium" style="color: var(--dash-text)">All supplier items confirmed</p>
                        </div>
                    @endforelse
                </div>

                {{-- Today's customer payments --}}
                <div class="dash-card overflow-hidden" style="border-top: 2px solid var(--dash-success);">
                    <div class="flex items-center justify-between px-3.5 pt-3 pb-2">
                        <h3 class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--dash-text-3)">Today's payments</h3>
                        <span class="text-[11px] font-semibold" style="color: var(--dash-success)">{{ number_format($todayCustomerPaymentTotal, 0) }}</span>
                    </div>
                    @forelse ($todayCustomerPayments as $payment)
                        <a href="{{ route('bookings.show', $payment->booking) }}" class="dash-row flex items-center gap-2 px-3.5 py-2 border-t" style="border-color: var(--dash-border)">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background: var(--dash-success)"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium truncate" style="color: var(--dash-text)">{{ $payment->booking->customer->name }}</p>
                                <p class="text-[10px] truncate" style="color: var(--dash-text-3)">{{ $payment->method->label() }}</p>
                            </div>
                            <p class="shrink-0 text-xs font-semibold tabular-nums" style="color: var(--dash-success)">{{ number_format($payment->amount, 0) }}</p>
                        </a>
                    @empty
                        <div class="flex items-center gap-2 px-3.5 py-4 border-t" style="border-color: var(--dash-border)">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" style="background: var(--dash-surface-2); color: var(--dash-text-3)">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><circle cx="12" cy="12" r="9" /></svg>
                            </span>
                            <p class="text-xs font-medium" style="color: var(--dash-text)">No payments today</p>
                        </div>
                    @endforelse
                </div>

                {{-- Supplier payments needing attention --}}
                <div class="dash-card overflow-hidden" style="border-top: 2px solid {{ $supplierPaymentsNeedingAttention->isNotEmpty() ? 'var(--dash-overdue)' : 'var(--dash-supplier)' }};">
                    <div class="flex items-center justify-between px-3.5 pt-3 pb-2">
                        <h3 class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--dash-text-3)">Supplier payments due</h3>
                    </div>
                    @forelse ($supplierPaymentsNeedingAttention as $item)
                        <a href="{{ route('bookings.show', $item->booking) }}" class="dash-row flex items-center gap-2 px-3.5 py-2 border-t" style="border-color: var(--dash-border)">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background: var(--dash-overdue)"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium truncate" style="color: var(--dash-text)">{{ $item->supplier?->name ?? $item->supplier_name_snapshot ?? 'No supplier' }}</p>
                                <p class="text-[10px] truncate" style="color: var(--dash-text-3)">{{ $item->booking->trip->destination }}</p>
                            </div>
                            <p class="shrink-0 text-xs font-semibold tabular-nums" style="color: var(--dash-overdue)">{{ number_format($item->amountPayable(), 0) }}</p>
                        </a>
                    @empty
                        <div class="flex items-center gap-2 px-3.5 py-4 border-t" style="border-color: var(--dash-border)">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" style="background: rgba(16,185,129,.12); color: var(--dash-success)">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </span>
                            <p class="text-xs font-medium" style="color: var(--dash-text)">No supplier payments due</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Needs attention today / Recent activity (real activity-log feed) / Recent leads & enquiries --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 mb-6">
                <div class="lg:col-span-5">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[13px] font-semibold" style="color: var(--dash-text)">Needs attention today</h3>
                        <a href="{{ route('follow-ups.index') }}" class="text-xs font-medium" style="color: var(--dash-primary)">View all</a>
                    </div>
                    <div class="dash-card overflow-hidden">
                        @forelse ($attentionItems as $entry)
                            @php $followUp = $entry['followUp']; @endphp
                            <div class="dash-row flex items-center gap-3 px-4 py-3 border-b" style="border-color: var(--dash-border)">
                                <span class="shrink-0 flex h-9 w-9 items-center justify-center rounded-lg" style="background: {{ $entry['overdue'] ? 'rgba(239,68,68,.12)' : 'rgba(245,158,11,.12)' }}; color: {{ $entry['overdue'] ? 'var(--dash-danger)' : 'var(--dash-warning)' }}">
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v4.638c0 .225.12.433.315.546l3.25 1.875a.75.75 0 00.75-1.3L10.75 9.06V5z" clip-rule="evenodd" /></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium truncate" style="color: var(--dash-text)">{{ $followUp->subjectLabel() }}</p>
                                    <p class="text-xs" style="color: {{ $entry['overdue'] ? 'var(--dash-danger)' : 'var(--dash-text-2)' }}">
                                        {{ $entry['overdue'] ? 'Overdue' : 'Due today' }} · {{ $followUp->due_at->format('g:i A') }}
                                        @if ($followUp->reason) &middot; {{ $followUp->reason }} @endif
                                    </p>
                                </div>
                                <a href="{{ $followUp->subjectUrl() }}" class="shrink-0 text-xs font-medium rounded-md px-2.5 py-1.5 transition-colors" style="color: var(--dash-text-2); border: 1px solid var(--dash-border)">Open</a>
                            </div>
                        @empty
                            <div class="flex items-center gap-3 px-4 py-5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" style="background: rgba(16,185,129,.12); color: var(--dash-success)">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-medium" style="color: var(--dash-text)">You're all caught up</p>
                                    <p class="text-xs" style="color: var(--dash-text-2)">No urgent follow-ups need your attention today.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Recent activity — real Activity log feed, causer + description + timestamp --}}
                <div class="lg:col-span-4">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[13px] font-semibold" style="color: var(--dash-text)">Recent activity</h3>
                    </div>
                    <div class="dash-card overflow-hidden">
                        @forelse ($recentActivity as $event)
                            <div class="dash-row flex items-start gap-3 px-4 py-2.5 border-b" style="border-color: var(--dash-border)">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full" style="background: var(--dash-insight); box-shadow: 0 0 5px var(--dash-insight);"></span>
                                <div class="mt-0.5 shrink-0"><x-avatar :name="$event->causer?->name ?? 'System'" size="sm" /></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm truncate" style="color: var(--dash-text)">
                                        <span class="font-medium">{{ $event->causer?->name ?? 'System' }}</span>
                                    </p>
                                    <p class="text-xs truncate" style="color: var(--dash-text-2)">{{ $event->description }}</p>
                                </div>
                                <p class="shrink-0 text-[11px] whitespace-nowrap mt-0.5" style="color: var(--dash-text-3)">{{ $event->created_at->diffForHumans(null, true) }}</p>
                            </div>
                        @empty
                            <div class="flex items-center gap-3 px-4 py-6">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" style="background: var(--dash-surface-2); color: var(--dash-text-3)">
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><circle cx="12" cy="12" r="9" /></svg>
                                </span>
                                <div>
                                    <p class="text-sm font-medium" style="color: var(--dash-text)">No recent activity</p>
                                    <p class="text-xs" style="color: var(--dash-text-2)">New leads, enquiries and bookings will appear here.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Recent leads & enquiries --}}
                <div class="lg:col-span-3">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[13px] font-semibold" style="color: var(--dash-text)">Recent leads &amp; enquiries</h3>
                        <a href="{{ route('leads.index') }}" class="text-xs font-medium" style="color: var(--dash-primary)">View all</a>
                    </div>
                    <div class="dash-card overflow-hidden">
                        @php
                            $pipeline = $recentLeads->map(fn ($l) => ['type' => 'lead', 'record' => $l, 'at' => $l->created_at])
                                ->concat($recentEnquiries->map(fn ($e) => ['type' => 'enquiry', 'record' => $e, 'at' => $e->created_at]))
                                ->sortByDesc('at')->take(6);
                        @endphp
                        @forelse ($pipeline as $entry)
                            @php
                                $record = $entry['record'];
                                $name = $entry['type'] === 'lead' ? $record->name : $record->customer->name;
                                $destination = $record->destination;
                                $url = $entry['type'] === 'lead' ? route('leads.show', $record) : route('enquiries.show', $record);
                            @endphp
                            <a href="{{ $url }}" class="dash-row flex items-center gap-2.5 px-3.5 py-2 border-t" style="border-color: var(--dash-border)">
                                <x-avatar :name="$name" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium truncate" style="color: var(--dash-text)">{{ $name }}</p>
                                    <p class="text-[10px] truncate" style="color: var(--dash-text-3)">{{ $entry['type'] === 'lead' ? 'Lead' : 'Enquiry' }} @if($destination) &middot; {{ $destination }} @endif</p>
                                </div>
                                <x-status-badge :color="$record->status->badgeColor()" :label="$record->status->label()" />
                            </a>
                        @empty
                            <div class="flex items-center gap-2 px-3.5 py-5 border-t" style="border-color: var(--dash-border)">
                                <p class="text-xs font-medium" style="color: var(--dash-text)">No leads or enquiries yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Recent bookings --}}
            <div>
                <div class="flex items-center justify-between mb-2.5">
                    <h3 class="text-[13px] font-semibold" style="color: var(--dash-text)">Recent bookings</h3>
                    <a href="{{ route('bookings.index') }}" class="text-xs font-medium" style="color: var(--dash-primary)">View all</a>
                </div>
                <div class="dash-card overflow-hidden">
                    @forelse ($recentBookings as $booking)
                        <a href="{{ route('bookings.show', $booking) }}" class="dash-row flex items-center gap-3 px-4 py-2.5 border-b" style="border-color: var(--dash-border)">
                            <x-avatar :name="$booking->customer->name" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium truncate" style="color: var(--dash-text)">{{ $booking->customer->name }}</p>
                                <p class="text-xs truncate" style="color: var(--dash-text-3)">
                                    {{ $booking->trip->destination }}
                                    @if ($booking->trip->start_date) &middot; {{ $booking->trip->start_date->format('d M Y') }} @endif
                                </p>
                            </div>
                            <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" />
                            <div class="shrink-0 text-right w-24">
                                @if ($booking->amountRemaining() > 0)
                                    <p class="text-sm font-semibold tabular-nums" style="color: var(--dash-warning)">{{ number_format($booking->amountRemaining(), 2) }}</p>
                                    <p class="text-[11px]" style="color: var(--dash-text-3)">due</p>
                                @else
                                    <p class="text-xs font-medium" style="color: var(--dash-success)">Paid in full</p>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="flex items-center gap-2.5 px-4 py-3">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--dash-text-3)"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><circle cx="12" cy="12" r="9" /></svg>
                            <p class="text-xs" style="color: var(--dash-text-2)"><span class="font-medium" style="color: var(--dash-text)">No bookings yet</span> &middot; confirmed bookings will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
@endsection

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
            $attentionBookingsCount = $bookingsNeedingAttention->count();

            $pipeline = $recentLeads->map(fn ($l) => ['type' => 'lead', 'record' => $l, 'at' => $l->created_at])
                ->concat($recentEnquiries->map(fn ($e) => ['type' => 'enquiry', 'record' => $e, 'at' => $e->created_at]))
                ->sortByDesc('at')->take(6);
        @endphp

        <div class="dash">
            {{-- ============ SECTION 1 — Welcome & actions ============ --}}
            <div class="dash-hero mb-5">
                <div class="dash-hero-main">
                    <p class="dash-eyebrow">
                        <span class="dash-pulse" style="background: {{ ($overdueCount > 0 || $attentionBookingsCount > 0) ? 'var(--dash-warning)' : 'var(--dash-success)' }}"></span>
                        {{ now()->format('l, j F Y') }}
                    </p>
                    <h1 class="dash-greeting">
                        {{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }}, {{ explode(' ', auth()->user()->name)[0] }}
                    </h1>
                    <p class="dash-greeting-sub">
                        @if ($overdueCount > 0 || $attentionBookingsCount > 0)
                            You have
                            @if ($attentionBookingsCount > 0)<strong>{{ $attentionBookingsCount }} {{ Str::plural('booking', $attentionBookingsCount) }}</strong> awaiting payment @endif
                            @if ($overdueCount > 0 && $attentionBookingsCount > 0) and @endif
                            @if ($overdueCount > 0)<strong>{{ $overdueCount }} overdue {{ Str::plural('follow-up', $overdueCount) }}</strong> @endif
                            that need your attention today.
                        @else
                            Everything's on track &mdash; no overdue work and no outstanding balances right now.
                        @endif
                    </p>
                </div>
                @hasanyrole('company_admin|company_user')
                    <div class="dash-hero-actions">
                        <a href="{{ route('bookings.create') }}" class="dash-btn-primary">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                            New booking
                        </a>
                        <a href="{{ route('leads.create') }}" class="dash-btn-ghost">Add lead</a>
                        <a href="{{ route('enquiries.create') }}" class="dash-btn-ghost">Add enquiry</a>
                    </div>
                @endhasanyrole
            </div>

            {{-- ============ SECTION 2 — Business performance (KPIs) ============ --}}
            <div class="dash-kpi-grid mb-5">
                {{-- New leads --}}
                <a href="{{ route('leads.index') }}" class="dash-card dash-kpi group">
                    <div class="dash-kpi-top">
                        <span class="dash-kpi-icon" style="background: {{ $newLeadsCount > 0 ? 'rgba(37,99,235,.1)' : 'var(--dash-surface-2)' }}; color: {{ $newLeadsCount > 0 ? 'var(--dash-primary)' : 'var(--dash-text-3)' }}">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 00-6 6c0 4.5 6 10 6 10s6-5.5 6-10a6 6 0 00-6-6zm0 8a2 2 0 110-4 2 2 0 010 4z" /></svg>
                        </span>
                        <span class="dash-kpi-link">View leads &rarr;</span>
                    </div>
                    <p class="dash-kpi-label">New leads</p>
                    <p class="dash-kpi-value {{ $newLeadsCount === 0 ? 'is-zero' : '' }}">{{ $newLeadsCount }}</p>
                    <p class="dash-kpi-sub">{{ $newLeadsCount > 0 ? 'In the last 7 days' : 'No new leads this week' }}</p>
                    <div class="dash-kpi-spark"><x-dash.sparkline :values="$leadsTrend" color="#2563eb" :height="28" /></div>
                </a>

                {{-- New enquiries --}}
                <a href="{{ route('enquiries.index') }}" class="dash-card dash-kpi group">
                    <div class="dash-kpi-top">
                        <span class="dash-kpi-icon" style="background: rgba(124,58,237,.1); color: var(--dash-enquiries)">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
                        </span>
                        <span class="dash-kpi-link">View enquiries &rarr;</span>
                    </div>
                    <p class="dash-kpi-label">New enquiries</p>
                    <div class="flex items-center gap-3 mt-1">
                        <x-dash.donut
                            :segments="[
                                ['value' => $enquiryStatusBreakdown['new'] ?? 0, 'color' => '#2563eb'],
                                ['value' => $enquiryStatusBreakdown['planning'] ?? 0, 'color' => '#d97706'],
                                ['value' => $enquiryStatusBreakdown['confirmed'] ?? 0, 'color' => '#059669'],
                                ['value' => ($enquiryStatusBreakdown['lost'] ?? 0) + ($enquiryStatusBreakdown['cancelled'] ?? 0), 'color' => '#cbd5e1'],
                            ]"
                            :center-value="$newEnquiriesCount"
                            center-label="" />
                        <div class="min-w-0 flex-1 space-y-1 text-[11px]" style="color: var(--dash-text-2)">
                            <p class="flex items-center justify-between gap-2"><span class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full shrink-0" style="background:#2563eb"></span>New</span><span class="font-semibold tabular-nums" style="color:var(--dash-text)">{{ $enquiryStatusBreakdown['new'] ?? 0 }}</span></p>
                            <p class="flex items-center justify-between gap-2"><span class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full shrink-0" style="background:#d97706"></span>Planning</span><span class="font-semibold tabular-nums" style="color:var(--dash-text)">{{ $enquiryStatusBreakdown['planning'] ?? 0 }}</span></p>
                            <p class="flex items-center justify-between gap-2"><span class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full shrink-0" style="background:#059669"></span>Confirmed</span><span class="font-semibold tabular-nums" style="color:var(--dash-text)">{{ $enquiryStatusBreakdown['confirmed'] ?? 0 }}</span></p>
                        </div>
                    </div>
                </a>

                {{-- Overdue follow-ups --}}
                <a href="{{ route('follow-ups.index') }}" class="dash-card dash-kpi group" style="{{ $overdueCount > 0 ? 'border-color: rgba(220,38,38,.25);' : '' }}">
                    <div class="dash-kpi-top">
                        <span class="dash-kpi-icon" style="background: {{ $overdueCount > 0 ? 'rgba(220,38,38,.1)' : 'rgba(5,150,105,.1)' }}; color: {{ $overdueCount > 0 ? 'var(--dash-danger)' : 'var(--dash-success)' }}">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v4.638c0 .225.12.433.315.546l3.25 1.875a.75.75 0 00.75-1.3L10.75 9.06V5z" clip-rule="evenodd" /></svg>
                        </span>
                        <span class="dash-kpi-link">View queue &rarr;</span>
                    </div>
                    <p class="dash-kpi-label">Overdue follow-ups</p>
                    <p class="dash-kpi-value" style="color: {{ $overdueCount > 0 ? 'var(--dash-danger)' : 'var(--dash-text)' }}">{{ $overdueCount }}</p>
                    <p class="dash-kpi-sub" style="color: {{ $overdueCount > 0 ? 'var(--dash-danger)' : 'var(--dash-success)' }}; font-weight: 600;">{{ $overdueCount > 0 ? 'Needs action now' : 'All caught up' }}</p>
                    <div class="dash-kpi-ratio">
                        <div style="width: {{ max(6, round($overdueCount / $followUpTotal * 100)) }}%; background: var(--dash-danger);"></div>
                        <div style="width: {{ max(6, round($todayCount / $followUpTotal * 100)) }}%; background: var(--dash-warning);"></div>
                    </div>
                    <p class="dash-kpi-sub mt-1">{{ $overdueCount }} overdue &middot; {{ $todayCount }} due today</p>
                </a>

                {{-- Payments today --}}
                <a href="{{ route('bookings.index') }}" class="dash-card dash-kpi group">
                    <div class="dash-kpi-top">
                        <span class="dash-kpi-icon" style="background: {{ $todayCustomerPaymentTotal > 0 ? 'rgba(5,150,105,.1)' : 'var(--dash-surface-2)' }}; color: {{ $todayCustomerPaymentTotal > 0 ? 'var(--dash-success)' : 'var(--dash-text-3)' }}">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12 1a1 1 0 00-1-1 1 1 0 00-1 1v.092a4.535 4.535 0 00-1.676.662C7.602 2.482 7 3.436 7 4.5c0 1.064.602 2.018 1.324 2.746.17.17.353.333.546.479v2.98a2.44 2.44 0 01-.966-.584 1 1 0 10-1.449 1.378A4.44 4.44 0 008.87 12.9V13.9a1 1 0 102 0v-.012c.709-.082 1.4-.33 1.983-.738.785-.551 1.397-1.397 1.397-2.413 0-1.016-.612-1.862-1.397-2.413a4.17 4.17 0 00-1.983-.738V4.65c.311.1.597.247.835.433a1 1 0 101.238-1.571A4.535 4.535 0 0012 1z" clip-rule="evenodd" /></svg>
                        </span>
                        <span class="dash-kpi-link">View bookings &rarr;</span>
                    </div>
                    <p class="dash-kpi-label">Payments today</p>
                    <p class="dash-kpi-value {{ $todayCustomerPaymentTotal == 0 ? 'is-zero' : '' }}">{{ number_format($todayCustomerPaymentTotal, 0) }}</p>
                    <p class="dash-kpi-sub">{{ $todayCustomerPaymentTotal > 0 ? 'Received today' : 'No payments received today' }} &middot; {{ number_format($weeklyPaymentsTotal, 0) }} this week</p>
                    <div class="dash-kpi-spark"><x-dash.sparkline :values="$paymentsTrend" color="#059669" :height="28" /></div>
                </a>
            </div>

            {{-- ============ SECTION 3 — Operational health ============ --}}
            <div class="dash-section-head">
                <h3>Operational health</h3>
            </div>
            <div class="dash-ops-grid mb-5">
                {{-- Bookings needing attention --}}
                <div class="dash-card dash-ops-card">
                    <div class="dash-ops-card-head">
                        <span class="dash-ops-dot" style="background: {{ $attentionBookingsCount > 0 ? 'var(--dash-warning)' : 'var(--dash-success)' }}"></span>
                        <span class="dash-ops-title">Bookings needing attention</span>
                        @if ($attentionBookingsCount > 0)
                            <span class="dash-ops-badge" style="color: var(--dash-warning); background: rgba(217,119,6,.1)">{{ number_format($bookingsNeedingAttention->sum(fn ($b) => $b->amountRemaining()), 0) }}</span>
                        @endif
                    </div>
                    @forelse ($bookingsNeedingAttention->take(3) as $booking)
                        <a href="{{ route('bookings.show', $booking) }}" class="dash-row dash-ops-row">
                            <div class="min-w-0 flex-1">
                                <p class="dash-ops-row-name">{{ $booking->customer->name }}</p>
                                <p class="dash-ops-row-meta">{{ $booking->trip->destination }}</p>
                            </div>
                            <p class="dash-ops-row-amount">{{ number_format($booking->amountRemaining(), 0) }}</p>
                        </a>
                    @empty
                        <p class="dash-ops-healthy">No outstanding balances</p>
                    @endforelse
                    <a href="{{ route('bookings.index') }}" class="dash-ops-action">Review bookings &rarr;</a>
                </div>

                {{-- Unconfirmed supplier items --}}
                <div class="dash-card dash-ops-card">
                    <div class="dash-ops-card-head">
                        <span class="dash-ops-dot" style="background: {{ $unconfirmedSupplierItems->isNotEmpty() ? 'var(--dash-supplier)' : 'var(--dash-success)' }}"></span>
                        <span class="dash-ops-title">Unconfirmed supplier items</span>
                        @if ($unconfirmedSupplierItems->isNotEmpty())
                            <span class="dash-ops-badge" style="color: var(--dash-supplier); background: rgba(180,83,9,.1)">{{ $unconfirmedSupplierItems->count() }}</span>
                        @endif
                    </div>
                    @forelse ($unconfirmedSupplierItems->take(3) as $item)
                        <a href="{{ route('bookings.show', $item->booking) }}" class="dash-row dash-ops-row">
                            <div class="min-w-0 flex-1">
                                <p class="dash-ops-row-name">{{ $item->description }}</p>
                                <p class="dash-ops-row-meta">{{ $item->supplier?->name ?? $item->supplier_name_snapshot ?? 'No supplier' }}</p>
                            </div>
                            <x-status-badge :color="$item->status->badgeColor()" :label="$item->status->label()" />
                        </a>
                    @empty
                        <p class="dash-ops-healthy">All supplier items confirmed</p>
                    @endforelse
                    <a href="{{ route('bookings.index') }}" class="dash-ops-action">Review items &rarr;</a>
                </div>

                {{-- Today's customer payments --}}
                <div class="dash-card dash-ops-card">
                    <div class="dash-ops-card-head">
                        <span class="dash-ops-dot" style="background: {{ $todayCustomerPaymentTotal > 0 ? 'var(--dash-success)' : 'var(--dash-text-3)' }}"></span>
                        <span class="dash-ops-title">Today's collections</span>
                        <span class="dash-ops-badge {{ $todayCustomerPaymentTotal > 0 ? '' : 'is-neutral' }}" style="color: var(--dash-success); background: rgba(5,150,105,.1)">{{ number_format($todayCustomerPaymentTotal, 0) }}</span>
                    </div>
                    @forelse ($todayCustomerPayments->take(3) as $payment)
                        <a href="{{ route('bookings.show', $payment->booking) }}" class="dash-row dash-ops-row">
                            <div class="min-w-0 flex-1">
                                <p class="dash-ops-row-name">{{ $payment->booking->customer->name }}</p>
                                <p class="dash-ops-row-meta">{{ $payment->method->label() }}</p>
                            </div>
                            <p class="dash-ops-row-amount" style="color: var(--dash-success)">{{ number_format($payment->amount, 0) }}</p>
                        </a>
                    @empty
                        <p class="dash-ops-healthy">No payments today</p>
                    @endforelse
                    <a href="{{ route('bookings.index') }}" class="dash-ops-action">View collections &rarr;</a>
                </div>

                {{-- Supplier payments needing attention --}}
                <div class="dash-card dash-ops-card">
                    <div class="dash-ops-card-head">
                        <span class="dash-ops-dot" style="background: {{ $supplierPaymentsNeedingAttention->isNotEmpty() ? 'var(--dash-overdue)' : 'var(--dash-success)' }}"></span>
                        <span class="dash-ops-title">Supplier payments due</span>
                        @if ($supplierPaymentsNeedingAttention->isNotEmpty())
                            <span class="dash-ops-badge" style="color: var(--dash-overdue); background: rgba(225,29,72,.1)">{{ number_format($supplierPaymentsNeedingAttention->sum(fn ($i) => $i->amountPayable()), 0) }}</span>
                        @endif
                    </div>
                    @forelse ($supplierPaymentsNeedingAttention->take(3) as $item)
                        <a href="{{ route('bookings.show', $item->booking) }}" class="dash-row dash-ops-row">
                            <div class="min-w-0 flex-1">
                                <p class="dash-ops-row-name">{{ $item->supplier?->name ?? $item->supplier_name_snapshot ?? 'No supplier' }}</p>
                                <p class="dash-ops-row-meta">{{ $item->booking->trip->destination }}</p>
                            </div>
                            <p class="dash-ops-row-amount" style="color: var(--dash-overdue)">{{ number_format($item->amountPayable(), 0) }}</p>
                        </a>
                    @empty
                        <p class="dash-ops-healthy">No supplier payments due</p>
                    @endforelse
                    <a href="{{ route('bookings.index') }}" class="dash-ops-action">Review payables &rarr;</a>
                </div>
            </div>

            {{-- ============ SECTION 4 — Priority workspace ============ --}}
            <div class="dash-priority-grid mb-5">
                {{-- Needs attention today (primary, ~62%) --}}
                <div>
                    <div class="dash-section-head">
                        <h3>Needs attention today</h3>
                        <a href="{{ route('follow-ups.index') }}" class="dash-link">View all</a>
                    </div>
                    <div class="dash-card overflow-hidden">
                        @forelse ($attentionItems as $entry)
                            @php $followUp = $entry['followUp']; @endphp
                            <a href="{{ $followUp->subjectUrl() }}" class="dash-row dash-attn-row">
                                <span class="dash-attn-icon" style="background: {{ $entry['overdue'] ? 'rgba(220,38,38,.1)' : 'rgba(217,119,6,.1)' }}; color: {{ $entry['overdue'] ? 'var(--dash-danger)' : 'var(--dash-warning)' }}">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v4.638c0 .225.12.433.315.546l3.25 1.875a.75.75 0 00.75-1.3L10.75 9.06V5z" clip-rule="evenodd" /></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="dash-attn-name">{{ $followUp->subjectLabel() }}</p>
                                    <p class="dash-attn-meta" style="color: {{ $entry['overdue'] ? 'var(--dash-danger)' : 'var(--dash-text-2)' }}">
                                        <span class="font-semibold">{{ $entry['overdue'] ? 'Overdue' : 'Due today' }}</span> &middot; {{ $followUp->due_at->format('g:i A') }}
                                        @if ($followUp->reason) &middot; {{ $followUp->reason }} @endif
                                    </p>
                                </div>
                                <span class="dash-attn-cta">Open</span>
                            </a>
                        @empty
                            <div class="dash-empty-compact">
                                <span class="dash-empty-icon">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                </span>
                                <div>
                                    <p class="dash-empty-title">You're all caught up</p>
                                    <p class="dash-empty-sub">No urgent follow-ups need your attention today.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Recent activity (secondary, ~38%) — compact timeline --}}
                <div>
                    <div class="dash-section-head">
                        <h3>Recent activity</h3>
                    </div>
                    <div class="dash-card dash-timeline-card">
                        @forelse ($recentActivity as $event)
                            <div class="dash-timeline-row">
                                <div class="dash-timeline-rail">
                                    <span class="dash-timeline-dot"></span>
                                    <span class="dash-timeline-line"></span>
                                </div>
                                <div class="min-w-0 flex-1 pb-3">
                                    <p class="dash-timeline-text">
                                        <span class="font-semibold" style="color: var(--dash-text)">{{ $event->causer?->name ?? 'System' }}</span>
                                        <span style="color: var(--dash-text-2)">{{ $event->description }}</span>
                                    </p>
                                    <p class="dash-timeline-time">{{ $event->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="dash-empty-compact">
                                <span class="dash-empty-icon dash-empty-icon-muted">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><circle cx="12" cy="12" r="9" /></svg>
                                </span>
                                <div>
                                    <p class="dash-empty-title">No recent activity</p>
                                    <p class="dash-empty-sub">New leads, enquiries and bookings will appear here.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ============ SECTION 5 — Business pipeline ============ --}}
            <div class="dash-pipeline-grid">
                {{-- Recent bookings (left, wider) --}}
                <div>
                    <div class="dash-section-head">
                        <h3>Recent bookings</h3>
                        <a href="{{ route('bookings.index') }}" class="dash-link">View all</a>
                    </div>
                    <div class="dash-card overflow-hidden">
                        @forelse ($recentBookings as $booking)
                            <a href="{{ route('bookings.show', $booking) }}" class="dash-row dash-booking-row">
                                <div class="dash-booking-ref">#{{ $booking->id }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="dash-booking-name">{{ $booking->customer->name }}</p>
                                    <p class="dash-booking-meta">
                                        {{ $booking->trip->destination }}
                                        @if ($booking->trip->start_date) &middot; {{ $booking->trip->start_date->format('d M Y') }} @endif
                                    </p>
                                </div>
                                <x-status-badge :color="$booking->status->badgeColor()" :label="$booking->status->label()" />
                                <div class="dash-booking-amount">
                                    @if ($booking->amountRemaining() > 0)
                                        <p class="dash-booking-due">{{ number_format($booking->amountRemaining(), 2) }}</p>
                                        <p class="dash-booking-due-label">due</p>
                                    @else
                                        <p class="dash-booking-paid">Paid in full</p>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="dash-empty-compact">
                                <span class="dash-empty-icon dash-empty-icon-muted">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><circle cx="12" cy="12" r="9" /></svg>
                                </span>
                                <div>
                                    <p class="dash-empty-title">No bookings yet</p>
                                    <p class="dash-empty-sub">Confirmed bookings will appear here.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Recent leads & enquiries (right) --}}
                <div>
                    <div class="dash-section-head">
                        <h3>Recent leads &amp; enquiries</h3>
                        <a href="{{ route('leads.index') }}" class="dash-link">View all</a>
                    </div>
                    <div class="dash-card overflow-hidden">
                        @forelse ($pipeline as $entry)
                            @php
                                $record = $entry['record'];
                                $name = $entry['type'] === 'lead' ? $record->name : $record->customer->name;
                                $destination = $record->destination;
                                $url = $entry['type'] === 'lead' ? route('leads.show', $record) : route('enquiries.show', $record);
                            @endphp
                            <a href="{{ $url }}" class="dash-row dash-pipeline-row">
                                <x-avatar :name="$name" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <p class="dash-pipeline-name">{{ $name }}</p>
                                    <p class="dash-pipeline-meta">
                                        <span class="dash-pipeline-type">{{ $entry['type'] === 'lead' ? 'Lead' : 'Enquiry' }}</span>
                                        @if($destination) &middot; {{ $destination }} @endif
                                        &middot; {{ $entry['at']->format('d M') }}
                                    </p>
                                </div>
                                <x-status-badge :color="$record->status->badgeColor()" :label="$record->status->label()" />
                            </a>
                        @empty
                            <div class="dash-empty-compact">
                                <div>
                                    <p class="dash-empty-title">No leads or enquiries yet</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

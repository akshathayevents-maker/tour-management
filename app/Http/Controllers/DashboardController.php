<?php

namespace App\Http\Controllers;

use App\Enums\BookingItemStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\CustomerPayment;
use App\Models\Enquiry;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return view('dashboard');
        }

        $overdueFollowUps = FollowUp::query()->overdue()->with('followupable')->orderBy('due_at')->limit(5)->get();
        $todayFollowUps = FollowUp::query()->dueToday()->with('followupable')->orderBy('due_at')->limit(5)->get();

        $newLeadsCount = Lead::query()->where('created_at', '>=', now()->subDays(7))->count();
        $newEnquiriesCount = Enquiry::query()->where('created_at', '>=', now()->subDays(7))->count();

        // Real daily buckets for the KPI sparklines — whereDate() per day
        // rather than a raw DATE() groupBy, so it stays driver-agnostic
        // across pgsql (dev) and sqlite (tests) without hand-rolled SQL.
        $leadsTrend = collect(range(6, 0))->map(
            fn (int $daysAgo) => Lead::query()->whereDate('created_at', now()->subDays($daysAgo)->toDateString())->count()
        )->all();

        $enquiriesTrend = collect(range(6, 0))->map(
            fn (int $daysAgo) => Enquiry::query()->whereDate('created_at', now()->subDays($daysAgo)->toDateString())->count()
        )->all();

        $paymentsTrend = collect(range(6, 0))->map(
            fn (int $daysAgo) => (float) CustomerPayment::query()
                ->whereDate('paid_at', now()->subDays($daysAgo)->toDateString())
                ->whereNull('voided_at')
                ->sum('amount')
        )->all();

        $enquiryStatusBreakdown = Enquiry::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $recentLeads = Lead::query()->latest()->limit(5)->get();
        $recentEnquiries = Enquiry::query()->with('customer')->latest()->limit(5)->get();

        // Bounded to the 100 most recently confirmed bookings — the balance
        // itself reuses Booking::amountRemaining() rather than a second,
        // competing SQL sum, so filtering happens in PHP over a capped set.
        $confirmedBookings = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->with(['customer', 'trip', 'items', 'payments'])
            ->latest('confirmed_at')
            ->limit(100)
            ->get();

        $bookingsNeedingAttention = $confirmedBookings
            ->filter(fn (Booking $booking) => $booking->amountRemaining() > 0.0)
            ->sortByDesc(fn (Booking $booking) => $booking->amountRemaining())
            ->take(5)
            ->values();

        $unconfirmedSupplierItems = BookingItem::query()
            ->whereIn('status', [BookingItemStatus::Pending, BookingItemStatus::Requested])
            ->whereHas('booking', fn ($query) => $query->where('status', BookingStatus::Confirmed))
            ->with(['booking.customer', 'booking.trip', 'supplier'])
            ->orderBy('start_at')
            ->limit(8)
            ->get();

        $todayCustomerPayments = CustomerPayment::query()
            ->whereDate('paid_at', now()->toDateString())
            ->whereNull('voided_at')
            ->with(['booking.customer', 'booking.trip'])
            ->latest('paid_at')
            ->limit(10)
            ->get();

        $todayCustomerPaymentTotal = (float) CustomerPayment::query()
            ->whereDate('paid_at', now()->toDateString())
            ->whereNull('voided_at')
            ->sum('amount');

        // Same bounding rationale as bookingsNeedingAttention — reuses
        // BookingItem::amountPayable() instead of a duplicate SQL sum.
        $payableCandidates = BookingItem::query()
            ->whereNotNull('cost')
            ->where('status', '!=', BookingItemStatus::Cancelled)
            ->whereHas('booking', fn ($query) => $query->where('status', BookingStatus::Confirmed))
            ->with(['supplier', 'supplierPayments', 'booking.customer', 'booking.trip'])
            ->limit(100)
            ->get();

        $supplierPaymentsNeedingAttention = $payableCandidates
            ->filter(fn (BookingItem $item) => $item->amountPayable() > 0.0)
            ->sortByDesc(fn (BookingItem $item) => $item->amountPayable())
            ->take(8)
            ->values();

        $recentBookings = Booking::query()
            ->with(['customer', 'trip', 'items', 'payments'])
            ->latest()
            ->limit(5)
            ->get();

        // Real activity-log feed scoped by causer (every log() call in this
        // app is causedBy() the acting user, and users belong to one
        // company) — avoids a per-subject-type join across the polymorphic
        // `subject` relation since activity_log itself has no company_id.
        $companyUserIds = User::query()->where('company_id', $user->company_id)->pluck('id');
        $recentActivity = Activity::query()
            ->where('causer_type', User::class)
            ->whereIn('causer_id', $companyUserIds)
            ->with('causer')
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'overdueFollowUps', 'todayFollowUps',
            'newLeadsCount', 'newEnquiriesCount',
            'leadsTrend', 'enquiriesTrend', 'paymentsTrend', 'enquiryStatusBreakdown',
            'recentLeads', 'recentEnquiries', 'recentActivity',
            'bookingsNeedingAttention', 'unconfirmedSupplierItems',
            'todayCustomerPayments', 'todayCustomerPaymentTotal',
            'supplierPaymentsNeedingAttention', 'recentBookings',
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\BookingItemStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MoneyController extends Controller
{
    /**
     * Read-only cross-booking financial summary — "who owes us" and "who
     * we owe" — aggregated in SQL rather than by loading every Booking/
     * BookingItem model, since the formulas below are proven equivalent
     * to Booking::amountRemaining() / BookingItem::amountPayable() (see
     * DashboardTest's consistency assertions and MoneyTest).
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);

        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return view('money', [
                'customerBalances' => new LengthAwarePaginator([], 0, 20),
                'supplierBalances' => new LengthAwarePaginator([], 0, 20),
                'customerTotal' => 0.0,
                'supplierTotal' => 0.0,
                'customerSort' => 'outstanding',
                'supplierSort' => 'payable',
            ]);
        }

        $companyId = $user->company_id;

        // Only Confirmed bookings are "active/relevant" for money-owed
        // purposes — the same rule the Dashboard already established for
        // bookingsNeedingAttention/supplierPaymentsNeedingAttention, so
        // this page can never disagree with the Dashboard about which
        // bookings count.
        //
        // totalSellPrice() sums every item on the booking regardless of
        // item status (Booking::totalSellPrice() applies no status
        // filter), so the sell-price subquery here mirrors that exactly.
        // totalPaid() sums only non-voided CustomerPayments, mirrored by
        // the "voided_at is null" condition below.
        $bookingBalances = DB::table('bookings')
            ->where('bookings.company_id', $companyId)
            ->where('bookings.status', BookingStatus::Confirmed->value)
            ->select('bookings.customer_id', DB::raw(
                '(select coalesce(sum(bi.sell_price), 0) from booking_items bi where bi.booking_id = bookings.id)'
                .' - (select coalesce(sum(cp.amount), 0) from customer_payments cp where cp.booking_id = bookings.id and cp.voided_at is null)'
                .' as remaining'
            ));

        $customerRows = DB::query()->fromSub($bookingBalances, 'b')
            ->join('customers', 'customers.id', '=', 'b.customer_id')
            ->where('customers.company_id', $companyId)
            ->groupBy('customers.id', 'customers.name')
            ->havingRaw('sum(b.remaining) > 0')
            ->select('customers.id as id', 'customers.name as name', DB::raw('sum(b.remaining) as outstanding'))
            ->get();

        $customerTotal = (float) $customerRows->sum('outstanding');

        // amountPayable() = cost - totalPaidToSupplier(), where
        // totalPaidToSupplier() sums only non-voided SupplierPayments and
        // amountPayable() is null (excluded) when cost is null. Cancelled
        // items are excluded here, mirroring the Dashboard's
        // supplierPaymentsNeedingAttention filter.
        $itemBalances = DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->where('bookings.company_id', $companyId)
            ->where('bookings.status', BookingStatus::Confirmed->value)
            ->where('booking_items.status', '!=', BookingItemStatus::Cancelled->value)
            ->whereNotNull('booking_items.cost')
            ->select('booking_items.supplier_id', DB::raw(
                'booking_items.cost - (select coalesce(sum(sp.amount), 0) from supplier_payments sp where sp.booking_item_id = booking_items.id and sp.voided_at is null)'
                .' as payable'
            ));

        $supplierRows = DB::query()->fromSub($itemBalances, 'i')
            ->join('suppliers', 'suppliers.id', '=', 'i.supplier_id')
            ->where('suppliers.company_id', $companyId)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->havingRaw('sum(i.payable) > 0')
            ->select('suppliers.id as id', 'suppliers.name as name', DB::raw('sum(i.payable) as payable'))
            ->get();

        $supplierTotal = (float) $supplierRows->sum('payable');

        $customerSort = $request->string('customer_sort', 'outstanding')->value();
        $customerRows = $customerSort === 'name'
            ? $customerRows->sortBy('name')->values()
            : $customerRows->sortByDesc('outstanding')->values();

        $supplierSort = $request->string('supplier_sort', 'payable')->value();
        $supplierRows = $supplierSort === 'name'
            ? $supplierRows->sortBy('name')->values()
            : $supplierRows->sortByDesc('payable')->values();

        $customerBalances = $this->paginate($customerRows, $request, 'customer_page');
        $supplierBalances = $this->paginate($supplierRows, $request, 'supplier_page');

        return view('money', compact(
            'customerBalances', 'supplierBalances',
            'customerTotal', 'supplierTotal',
            'customerSort', 'supplierSort',
        ));
    }

    private function paginate(\Illuminate\Support\Collection $rows, Request $request, string $pageName, int $perPage = 20): LengthAwarePaginator
    {
        $page = (int) $request->query($pageName, 1);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => $pageName]
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\SupplierType;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\BookingItem;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'types' => SupplierType::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', "Supplier \"{$supplier->name}\" added.");
    }

    public function show(Supplier $supplier): View
    {
        $this->authorize('view', $supplier);

        $supplier->load(['bookingItems.booking.customer', 'bookingItems.booking.trip', 'bookingItems.supplierPayments']);

        // Aggregated by summing each item's own existing calculation
        // methods (BookingItem::totalPaidToSupplier() / amountPayable())
        // — same approach already established on the Booking detail hero
        // and the Money page's SQL equivalent — never a second, competing
        // formula.
        $totalCost = (float) $supplier->bookingItems->sum('cost');
        $totalPaid = (float) $supplier->bookingItems->sum(fn (BookingItem $item) => $item->totalPaidToSupplier());
        $totalPayable = (float) $supplier->bookingItems->sum(fn (BookingItem $item) => max(0, $item->amountPayable() ?? 0));

        $recentPayments = $supplier->bookingItems
            ->flatMap(fn (BookingItem $item) => $item->supplierPayments->map(fn ($payment) => tap($payment, fn ($p) => $p->setRelation('bookingItem', $item))))
            ->sortByDesc('paid_at')
            ->take(10)
            ->values();

        return view('suppliers.show', compact('supplier', 'totalCost', 'totalPaid', 'totalPayable', 'recentPayments'));
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', 'Supplier updated.');
    }
}

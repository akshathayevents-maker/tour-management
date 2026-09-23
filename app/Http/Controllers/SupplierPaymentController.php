<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierPayment\StoreSupplierPaymentRequest;
use App\Http\Requests\SupplierPayment\VoidSupplierPaymentRequest;
use App\Models\BookingItem;
use App\Models\SupplierPayment;
use Illuminate\Http\RedirectResponse;

class SupplierPaymentController extends Controller
{
    public function store(StoreSupplierPaymentRequest $request, BookingItem $bookingItem): RedirectResponse
    {
        $payment = $bookingItem->supplierPayments()->create([
            ...$request->validated(),
            'supplier_name_snapshot' => $bookingItem->supplier_name_snapshot ?? $bookingItem->supplier?->name,
            'supplier_phone_snapshot' => $bookingItem->supplier_phone_snapshot ?? $bookingItem->supplier?->phone,
        ]);

        activity()->performedOn($bookingItem->booking)->causedBy($request->user())
            ->log('Supplier payment of '.number_format($payment->amount, 2)." recorded for \"{$bookingItem->description}\".");

        return redirect()
            ->route('bookings.show', $bookingItem->booking)
            ->with('status', 'Supplier payment recorded.');
    }

    public function void(VoidSupplierPaymentRequest $request, SupplierPayment $supplierPayment): RedirectResponse
    {
        if ($supplierPayment->isVoided()) {
            abort(422, 'This payment has already been voided.');
        }

        $supplierPayment->void($request->string('reason')->value());

        activity()->performedOn($supplierPayment->bookingItem->booking)->causedBy($request->user())
            ->log('Supplier payment of '.number_format($supplierPayment->amount, 2).' voided.');

        return redirect()
            ->route('bookings.show', $supplierPayment->bookingItem->booking)
            ->with('status', 'Supplier payment voided.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPayment\StoreCustomerPaymentRequest;
use App\Http\Requests\CustomerPayment\VoidCustomerPaymentRequest;
use App\Models\Booking;
use App\Models\CustomerPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerPaymentController extends Controller
{
    public function store(StoreCustomerPaymentRequest $request, Booking $booking): RedirectResponse
    {
        $payment = $booking->payments()->create($request->validated());

        activity()->performedOn($booking)->causedBy($request->user())
            ->log('Payment of '.number_format($payment->amount, 2).' recorded ('.$payment->method->label().').');

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Payment recorded.');
    }

    public function void(VoidCustomerPaymentRequest $request, CustomerPayment $customerPayment): RedirectResponse
    {
        if ($customerPayment->isVoided()) {
            abort(422, 'This payment has already been voided.');
        }

        $customerPayment->void($request->string('reason')->value());

        activity()->performedOn($customerPayment->booking)->causedBy($request->user())
            ->log('Payment of '.number_format($customerPayment->amount, 2).' voided.');

        return redirect()
            ->route('bookings.show', $customerPayment->booking)
            ->with('status', 'Payment voided.');
    }

    public function receipt(CustomerPayment $customerPayment): View
    {
        $this->authorize('view', $customerPayment->booking);

        $customerPayment->load('booking.customer', 'booking.trip', 'booking.company');

        return view('customer_payments.receipt', compact('customerPayment'));
    }
}

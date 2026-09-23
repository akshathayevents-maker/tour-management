<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Invoice\CancelInvoiceRequest;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    public function store(StoreInvoiceRequest $request, Booking $booking, InvoiceService $invoices): RedirectResponse
    {
        try {
            $taxRate = $request->filled('tax_rate') ? (float) $request->input('tax_rate') : null;
            $invoice = $invoices->createForBooking($booking, $taxRate);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }

        activity()->performedOn($booking)->causedBy($request->user())
            ->log("Invoice {$invoice->invoice_number} issued.");

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice->booking);

        $invoice->load('lineItems', 'booking.customer', 'booking.trip', 'booking.company');

        return view('invoices.show', compact('invoice'));
    }

    public function cancel(CancelInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->isCancelled()) {
            abort(422, 'This invoice is already cancelled.');
        }

        $invoice->update([
            'status' => InvoiceStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->string('cancellation_reason')->value() ?: null,
        ]);

        activity()->performedOn($invoice->booking)->causedBy($request->user())
            ->log("Invoice {$invoice->invoice_number} cancelled.");

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Invoice cancelled.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\QuotationVersionStatus;
use App\Http\Requests\QuotationVersion\UpdateQuotationVersionRequest;
use App\Models\Booking;
use App\Models\QuotationVersion;
use App\Services\QuotationVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;

class QuotationVersionController extends Controller
{
    public function show(QuotationVersion $quotationVersion): View
    {
        $this->authorize('view', $quotationVersion->quotation);

        $quotationVersion->load('lineItems', 'quotation.trip.customer', 'quotation.versions');

        $existingBooking = Booking::where('accepted_quotation_version_id', $quotationVersion->id)->first();
        $activities = Activity::forSubject($quotationVersion)->with('causer')->latest()->limit(20)->get();

        return view('quotation_versions.show', compact('quotationVersion', 'existingBooking', 'activities'));
    }

    /**
     * Print-friendly customer-facing quotation — same authorization as
     * show(), same total-price method, chrome-free view. A Draft version
     * is not yet something a customer has been shown, so it isn't
     * printable (the lifecycle itself is untouched — this only gates
     * this read-only route).
     */
    public function print(QuotationVersion $quotationVersion): View
    {
        $this->authorize('view', $quotationVersion->quotation);

        if ($quotationVersion->status === QuotationVersionStatus::Draft) {
            abort(422, 'A draft quotation version cannot be printed.');
        }

        $quotationVersion->load(['lineItems', 'quotation.trip.customer', 'quotation.company']);

        return view('quotation_versions.print', compact('quotationVersion'));
    }

    public function update(UpdateQuotationVersionRequest $request, QuotationVersion $quotationVersion): RedirectResponse
    {
        if (! $quotationVersion->isEditable()) {
            abort(422, 'A sent version cannot be edited.');
        }

        $quotationVersion->update($request->validated());

        return redirect()
            ->route('quotation-versions.show', $quotationVersion)
            ->with('status', 'Quotation updated.');
    }

    public function send(QuotationVersion $quotationVersion, QuotationVersionService $versions): RedirectResponse
    {
        $this->authorize('update', $quotationVersion->quotation);

        try {
            $versions->send($quotationVersion);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['line_items' => $e->getMessage()]);
        }

        activity()->performedOn($quotationVersion)->causedBy(request()->user())->log("Quotation V{$quotationVersion->version_number} sent.");
        activity()->performedOn($quotationVersion->quotation->trip)->causedBy(request()->user())->log("Quotation V{$quotationVersion->version_number} sent.");

        return redirect()
            ->route('quotation-versions.show', $quotationVersion)
            ->with('status', "Version {$quotationVersion->version_number} sent.");
    }

    public function accept(QuotationVersion $quotationVersion, QuotationVersionService $versions): RedirectResponse
    {
        $this->authorize('update', $quotationVersion->quotation);

        try {
            $previousAccepted = $versions->accept($quotationVersion);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        activity()->performedOn($quotationVersion)->causedBy(request()->user())->log("Quotation V{$quotationVersion->version_number} accepted.");
        activity()->performedOn($quotationVersion->quotation->trip)->causedBy(request()->user())->log("Quotation V{$quotationVersion->version_number} accepted.");

        if ($previousAccepted) {
            $message = "Quotation V{$previousAccepted->version_number} superseded by V{$quotationVersion->version_number}.";
            activity()->performedOn($previousAccepted)->causedBy(request()->user())->log($message);
            activity()->performedOn($quotationVersion->quotation->trip)->causedBy(request()->user())->log($message);
        }

        return redirect()
            ->route('quotation-versions.show', $quotationVersion)
            ->with('status', 'Marked as accepted.');
    }

    public function reject(QuotationVersion $quotationVersion, QuotationVersionService $versions): RedirectResponse
    {
        $this->authorize('update', $quotationVersion->quotation);

        try {
            $versions->reject($quotationVersion);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        activity()->performedOn($quotationVersion)->causedBy(request()->user())->log("Quotation V{$quotationVersion->version_number} rejected.");
        activity()->performedOn($quotationVersion->quotation->trip)->causedBy(request()->user())->log("Quotation V{$quotationVersion->version_number} rejected.");

        return redirect()
            ->route('quotation-versions.show', $quotationVersion)
            ->with('status', 'Marked as rejected.');
    }

    public function newVersion(QuotationVersion $quotationVersion, QuotationVersionService $versions): RedirectResponse
    {
        $this->authorize('update', $quotationVersion->quotation);

        try {
            $newVersion = $versions->createNewVersion($quotationVersion);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['version' => $e->getMessage()]);
        }

        activity()->performedOn($newVersion)->causedBy(request()->user())
            ->log("Quotation V{$newVersion->version_number} created from V{$quotationVersion->version_number}.");
        activity()->performedOn($newVersion->quotation->trip)->causedBy(request()->user())
            ->log("Quotation V{$newVersion->version_number} created from V{$quotationVersion->version_number}.");

        return redirect()
            ->route('quotation-versions.show', $newVersion)
            ->with('status', "Version {$newVersion->version_number} created.");
    }
}

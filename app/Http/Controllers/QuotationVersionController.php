<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuotationVersion\UpdateQuotationVersionRequest;
use App\Models\QuotationVersion;
use App\Services\QuotationVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class QuotationVersionController extends Controller
{
    public function show(QuotationVersion $quotationVersion): View
    {
        $this->authorize('view', $quotationVersion->quotation);

        $quotationVersion->load('lineItems', 'quotation.trip.customer', 'quotation.versions');

        return view('quotation_versions.show', compact('quotationVersion'));
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

        return redirect()
            ->route('quotation-versions.show', $quotationVersion)
            ->with('status', "Version {$quotationVersion->version_number} sent.");
    }

    public function accept(QuotationVersion $quotationVersion, QuotationVersionService $versions): RedirectResponse
    {
        $this->authorize('update', $quotationVersion->quotation);

        try {
            $versions->accept($quotationVersion);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
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

        return redirect()
            ->route('quotation-versions.show', $newVersion)
            ->with('status', "Version {$newVersion->version_number} created.");
    }
}

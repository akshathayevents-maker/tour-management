<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quotation\StoreQuotationRequest;
use App\Models\Trip;
use App\Services\QuotationVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function store(StoreQuotationRequest $request, Trip $trip, QuotationVersionService $versions): RedirectResponse
    {
        if ($trip->quotation) {
            return redirect()
                ->route('quotation-versions.show', $trip->quotation->latestVersion())
                ->with('status', 'This trip already has a quotation.');
        }

        $version = DB::transaction(function () use ($trip, $versions) {
            $quotation = $trip->quotation()->create();

            return $versions->createFirstVersion($quotation);
        });

        return redirect()
            ->route('quotation-versions.show', $version)
            ->with('status', 'Quotation started. Add line items below.');
    }
}

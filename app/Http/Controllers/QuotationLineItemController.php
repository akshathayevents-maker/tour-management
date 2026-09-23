<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuotationLineItem\StoreQuotationLineItemRequest;
use App\Http\Requests\QuotationLineItem\UpdateQuotationLineItemRequest;
use App\Models\QuotationLineItem;
use App\Models\QuotationVersion;
use Illuminate\Http\RedirectResponse;

class QuotationLineItemController extends Controller
{
    public function store(StoreQuotationLineItemRequest $request, QuotationVersion $quotationVersion): RedirectResponse
    {
        if (! $quotationVersion->isEditable()) {
            abort(422, 'A sent version cannot be edited.');
        }

        $nextOrder = ($quotationVersion->lineItems()->max('sort_order') ?? -1) + 1;

        $quotationVersion->lineItems()->create([
            ...$request->validated(),
            'sort_order' => $nextOrder,
        ]);

        return redirect()
            ->route('quotation-versions.show', $quotationVersion)
            ->with('status', 'Line item added.');
    }

    public function update(UpdateQuotationLineItemRequest $request, QuotationLineItem $quotationLineItem): RedirectResponse
    {
        if (! $quotationLineItem->quotationVersion->isEditable()) {
            abort(422, 'A sent version cannot be edited.');
        }

        $quotationLineItem->update($request->validated());

        return redirect()
            ->route('quotation-versions.show', $quotationLineItem->quotationVersion)
            ->with('status', 'Line item updated.');
    }

    public function destroy(QuotationLineItem $quotationLineItem): RedirectResponse
    {
        $version = $quotationLineItem->quotationVersion;
        $this->authorize('update', $version->quotation);

        if (! $version->isEditable()) {
            abort(422, 'A sent version cannot be edited.');
        }

        $quotationLineItem->delete();

        return redirect()
            ->route('quotation-versions.show', $version)
            ->with('status', 'Line item removed.');
    }
}

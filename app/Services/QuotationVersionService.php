<?php

namespace App\Services;

use App\Enums\QuotationVersionStatus;
use App\Models\Quotation;
use App\Models\QuotationVersion;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Owns every quotation-version state transition, so "sent versions are
 * immutable" and "only one accepted version at a time" are enforced in one
 * place instead of scattered checks across controllers.
 */
class QuotationVersionService
{
    /**
     * The very first version for a brand-new quotation.
     */
    public function createFirstVersion(Quotation $quotation): QuotationVersion
    {
        return $quotation->versions()->create([
            'version_number' => 1,
            'status' => QuotationVersionStatus::Draft,
        ]);
    }

    /**
     * Fork a decided (sent/accepted/rejected) version into a new editable
     * draft, copying its line items and terms. The source version is never
     * touched — history stays intact.
     */
    public function createNewVersion(QuotationVersion $version): QuotationVersion
    {
        if ($version->isEditable()) {
            throw new InvalidArgumentException('A new version can only be created from a version that has already been sent.');
        }

        $quotation = $version->quotation;

        if ($quotation->versions->contains(fn ($v) => $v->isEditable())) {
            throw new InvalidArgumentException('This quotation already has an unsent draft version.');
        }

        return DB::transaction(function () use ($version, $quotation) {
            $nextNumber = $quotation->versions->max('version_number') + 1;

            $newVersion = $quotation->versions()->create([
                'version_number' => $nextNumber,
                'status' => QuotationVersionStatus::Draft,
                'valid_until' => $version->valid_until,
                'terms' => $version->terms,
            ]);

            foreach ($version->lineItems as $lineItem) {
                $newVersion->lineItems()->create([
                    'description' => $lineItem->description,
                    'category' => $lineItem->category,
                    'cost' => $lineItem->cost,
                    'sell_price' => $lineItem->sell_price,
                    'sort_order' => $lineItem->sort_order,
                ]);
            }

            return $newVersion;
        });
    }

    public function send(QuotationVersion $version): void
    {
        if (! $version->isEditable()) {
            throw new InvalidArgumentException('Only a draft version can be sent.');
        }

        if ($version->lineItems->isEmpty()) {
            throw new InvalidArgumentException('Add at least one line item before sending.');
        }

        $version->update([
            'status' => QuotationVersionStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function accept(QuotationVersion $version): void
    {
        if ($version->status !== QuotationVersionStatus::Sent) {
            throw new InvalidArgumentException('Only a sent version can be accepted.');
        }

        if ($version->isExpired()) {
            throw new InvalidArgumentException('This version has expired and can no longer be accepted. Create a new version instead.');
        }

        if ($version->quotation->acceptedVersion()) {
            throw new InvalidArgumentException('This quotation already has an accepted version.');
        }

        $version->update(['status' => QuotationVersionStatus::Accepted]);
    }

    public function reject(QuotationVersion $version): void
    {
        if ($version->status !== QuotationVersionStatus::Sent) {
            throw new InvalidArgumentException('Only a sent version can be rejected.');
        }

        $version->update(['status' => QuotationVersionStatus::Rejected]);
    }
}

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

    /**
     * Accept a sent version. If another version on this quotation is
     * currently Accepted (a revision scenario — see createNewVersion()),
     * that version is atomically moved to Superseded rather than blocking
     * the new acceptance, preserving the "at most one Accepted version"
     * invariant without ever leaving two versions Accepted at once.
     *
     * Returns the version that was just superseded, if any, so callers
     * (e.g. activity logging) can reference it — null when there was no
     * prior Accepted version to replace.
     */
    public function accept(QuotationVersion $version): ?QuotationVersion
    {
        if ($version->status !== QuotationVersionStatus::Sent) {
            throw new InvalidArgumentException('Only a sent version can be accepted.');
        }

        if ($version->isExpired()) {
            throw new InvalidArgumentException('This version has expired and can no longer be accepted. Create a new version instead.');
        }

        return DB::transaction(function () use ($version) {
            // Row-locked read-then-write, mirroring the same pattern
            // InvoiceService uses for its numbering race: closes the gap
            // where two concurrent accept() calls could otherwise both
            // observe "no accepted version yet" under READ COMMITTED.
            $previousAccepted = QuotationVersion::query()
                ->where('quotation_id', $version->quotation_id)
                ->where('status', QuotationVersionStatus::Accepted)
                ->lockForUpdate()
                ->first();

            if ($previousAccepted && $previousAccepted->id !== $version->id) {
                $previousAccepted->update(['status' => QuotationVersionStatus::Superseded]);
            }

            $version->update(['status' => QuotationVersionStatus::Accepted]);

            return $previousAccepted;
        });
    }

    public function reject(QuotationVersion $version): void
    {
        if ($version->status !== QuotationVersionStatus::Sent) {
            throw new InvalidArgumentException('Only a sent version can be rejected.');
        }

        $version->update(['status' => QuotationVersionStatus::Rejected]);
    }
}

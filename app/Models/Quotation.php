<?php

namespace App\Models;

use App\Enums\QuotationVersionStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['trip_id'])]
class Quotation extends Model
{
    use BelongsToCompany, HasFactory;

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(QuotationVersion::class)->orderByDesc('version_number');
    }

    public function latestVersion(): ?QuotationVersion
    {
        return $this->versions->first();
    }

    /**
     * There should only ever be one accepted version at a time (accept()
     * on QuotationVersion is responsible for that invariant) — derived
     * rather than a stored accepted_version_id, so it can't drift.
     */
    public function acceptedVersion(): ?QuotationVersion
    {
        return $this->versions->firstWhere('status', QuotationVersionStatus::Accepted);
    }
}

<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apply to every tenant-owned model (Customer, Lead, Booking, ...).
 *
 * - Auto-scopes all queries to the current user's company (CompanyScope).
 * - Auto-fills company_id on create from the authenticated user, so
 *   controllers/forms never need to (and can't) set it themselves.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (blank($model->company_id) && auth()->check() && ! auth()->user()->isSuperAdmin()) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

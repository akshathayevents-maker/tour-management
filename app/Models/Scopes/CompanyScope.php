<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query to the authenticated user's company.
 *
 * Super admins (no company_id) bypass this scope entirely so they can
 * manage all tenants. The scope reads company_id only from the
 * authenticated session — never from request input — so a tenant can't
 * escape isolation by editing IDs in a URL or payload.
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            return;
        }

        $builder->where($model->qualifyColumn('company_id'), $user->company_id);
    }
}

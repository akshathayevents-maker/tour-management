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
        // auth()->hasUser() only reads the guard's cached user, unlike
        // auth()->user(): the latter re-resolves from the session on a cache
        // miss by querying the User model, which (being itself scoped by
        // this class) would call back into auth()->user() and recurse
        // forever. hasUser() short-circuits that first, still-unresolved
        // lookup so it runs unscoped, exactly once.
        if (! auth()->hasUser()) {
            return;
        }

        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            return;
        }

        $builder->where($model->qualifyColumn('company_id'), $user->company_id);
    }
}

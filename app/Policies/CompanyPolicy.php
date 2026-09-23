<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

/**
 * Companies are platform-level entities: only the Super Admin manages them.
 * Company staff never reach these routes at all, but the policy is the
 * hard backstop if a route/middleware mistake ever let them try.
 */
class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Company $company): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->isSuperAdmin();
    }
}

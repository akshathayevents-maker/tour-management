<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Business data, not platform data: company_admin and company_user manage
 * it within their own company. company_id match is the hard backstop
 * behind CompanyScope/route-model-binding — belt and suspenders against
 * any future query that forgets to apply the scope.
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->company_id === $customer->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->company_id === $customer->company_id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->company_id === $customer->company_id;
    }
}

<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->company_id === $supplier->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->company_id === $supplier->company_id;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->company_id === $supplier->company_id;
    }
}

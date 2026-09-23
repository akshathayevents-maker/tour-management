<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id;
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id && ! $lead->isConverted();
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->company_id === $lead->company_id;
    }
}

<?php

namespace App\Policies;

use App\Models\FollowUp;
use App\Models\User;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, FollowUp $followUp): bool
    {
        return $user->company_id === $followUp->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        return $user->company_id === $followUp->company_id;
    }

    public function delete(User $user, FollowUp $followUp): bool
    {
        return $user->company_id === $followUp->company_id;
    }
}

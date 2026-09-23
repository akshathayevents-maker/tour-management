<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Trip $trip): bool
    {
        return $user->company_id === $trip->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, Trip $trip): bool
    {
        return $user->company_id === $trip->company_id;
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->company_id === $trip->company_id;
    }
}

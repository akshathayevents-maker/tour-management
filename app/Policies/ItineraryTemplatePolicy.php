<?php

namespace App\Policies;

use App\Models\ItineraryTemplate;
use App\Models\User;

class ItineraryTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, ItineraryTemplate $itineraryTemplate): bool
    {
        return $user->company_id === $itineraryTemplate->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, ItineraryTemplate $itineraryTemplate): bool
    {
        return $user->company_id === $itineraryTemplate->company_id;
    }

    public function delete(User $user, ItineraryTemplate $itineraryTemplate): bool
    {
        return $user->company_id === $itineraryTemplate->company_id;
    }
}

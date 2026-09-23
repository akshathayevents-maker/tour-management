<?php

namespace App\Policies;

use App\Models\Enquiry;
use App\Models\User;

class EnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function view(User $user, Enquiry $enquiry): bool
    {
        return $user->company_id === $enquiry->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function update(User $user, Enquiry $enquiry): bool
    {
        return $user->company_id === $enquiry->company_id;
    }

    public function delete(User $user, Enquiry $enquiry): bool
    {
        return $user->company_id === $enquiry->company_id;
    }
}

<?php

namespace App\Policies;

use App\Models\User;

/**
 * Super admin: manages everyone.
 * Company admin: manages users within their own company only.
 * Company user: no user-management access.
 *
 * The company match is always against the authenticated user's own
 * company_id (never anything from the request), so tenant isolation
 * holds even if a route param is tampered with.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isCompanyAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $this->sameScope($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isCompanyAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $this->sameScope($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->sameScope($user, $target) && $user->isNot($target);
    }

    private function sameScope(User $user, User $target): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isCompanyAdmin()
            && $user->company_id !== null
            && $user->company_id === $target->company_id;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access for company users whose company (or own account) has been
 * deactivated. Super admins have no company and are never blocked here.
 */
class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isSuperAdmin()) {
            if (! $user->is_active || ! $user->company || ! $user->company->is_active) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw new AuthenticationException('Your company account has been deactivated. Please contact support.');
            }
        }

        return $next($request);
    }
}

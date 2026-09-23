<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\UserProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Staff management: super admin sees/creates across all companies,
 * company admin is scoped to their own company (enforced by the
 * CompanyScope global scope + UserPolicy — not by trusting request input).
 */
class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('company', 'roles')
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $companies = auth()->user()->isSuperAdmin()
            ? Company::query()->orderBy('name')->get()
            : collect();

        return view('admin.users.create', compact('companies'));
    }

    public function store(StoreUserRequest $request, UserProvisioningService $provisioning): RedirectResponse
    {
        $companyId = auth()->user()->isSuperAdmin()
            ? $request->integer('company_id')
            : auth()->user()->company_id;

        $user = $provisioning->createStaffUser([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'role' => $request->string('role'),
            'company_id' => $companyId,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "User \"{$user->name}\" invited.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->is_active ? 'User activated.' : 'User deactivated.');
    }
}

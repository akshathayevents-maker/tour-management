<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Super-admin-only: company (tenant) lifecycle management.
 */
class CompanyController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()->latest()->paginate(20);

        return view('admin.companies.index', compact('companies'));
    }

    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('admin.companies.create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = Company::create($request->validated());

        return redirect()
            ->route('admin.companies.index')
            ->with('status', "Company \"{$company->name}\" created.");
    }

    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        return view('admin.companies.edit', compact('company'));
    }

    public function update(StoreCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return redirect()
            ->route('admin.companies.index')
            ->with('status', "Company \"{$company->name}\" updated.");
    }

    public function toggleActive(Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $company->update(['is_active' => ! $company->is_active]);

        return back()->with('status', $company->is_active ? 'Company activated.' : 'Company deactivated.');
    }
}

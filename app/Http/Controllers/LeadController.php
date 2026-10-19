<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Services\LeadConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $leads = Lead::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('leads.index', [
            'leads' => $leads,
            'statuses' => LeadStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Lead::class);

        return view('leads.create');
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = Lead::create($request->validated());

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', "Lead \"{$lead->name}\" created.");
    }

    public function show(Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load('customer', 'enquiries');
        $followUps = $lead->followUps()->pending()->orderBy('due_at')->get();
        $activities = Activity::forSubject($lead)->with('causer')->latest()->limit(20)->get();

        return view('leads.show', compact('lead', 'followUps', 'activities'));
    }

    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        return view('leads.edit', compact('lead'));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $originalStatus = $lead->status;

        $lead->update($request->validated());

        if ($lead->status === LeadStatus::Lost && $originalStatus !== LeadStatus::Lost) {
            activity()->performedOn($lead)->causedBy(request()->user())
                ->log("Lead marked Lost: {$lead->lost_reason}");
        }

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Lead updated.');
    }

    public function convert(Lead $lead, LeadConversionService $conversion): RedirectResponse
    {
        $this->authorize('convert', $lead);

        $customer = $conversion->convert($lead);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Lead converted to customer \"{$customer->name}\".");
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\EnquiryStatus;
use App\Http\Requests\Enquiry\StoreEnquiryRequest;
use App\Http\Requests\Enquiry\UpdateEnquiryRequest;
use App\Models\Customer;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Enquiry::class);

        $enquiries = Enquiry::query()
            ->with('customer')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('enquiries.index', [
            'enquiries' => $enquiries,
            'statuses' => EnquiryStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Enquiry::class);

        // Pre-select the customer when arriving from a customer's profile.
        $customer = $request->filled('customer_id')
            ? Customer::findOrFail($request->integer('customer_id'))
            : null;

        return view('enquiries.create', compact('customer'));
    }

    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        $enquiry = Enquiry::create($request->validated());

        return redirect()
            ->route('enquiries.show', $enquiry)
            ->with('status', 'Enquiry created.');
    }

    public function show(Enquiry $enquiry): View
    {
        $this->authorize('view', $enquiry);

        $enquiry->load('customer', 'lead');
        $followUps = $enquiry->followUps()->pending()->orderBy('due_at')->get();

        return view('enquiries.show', compact('enquiry', 'followUps'));
    }

    public function edit(Enquiry $enquiry): View
    {
        $this->authorize('update', $enquiry);

        return view('enquiries.edit', compact('enquiry'));
    }

    public function update(UpdateEnquiryRequest $request, Enquiry $enquiry): RedirectResponse
    {
        $enquiry->update($request->validated());

        return redirect()
            ->route('enquiries.show', $enquiry)
            ->with('status', 'Enquiry updated.');
    }
}

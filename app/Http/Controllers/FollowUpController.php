<?php

namespace App\Http\Controllers;

use App\Enums\FollowUpStatus;
use App\Http\Requests\FollowUp\RescheduleFollowUpRequest;
use App\Http\Requests\FollowUp\StoreFollowUpRequest;
use App\Models\FollowUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', FollowUp::class);

        // reason/notes are the only directly-searchable columns on the
        // follow_ups table itself — the related subject (Lead/Customer/
        // Enquiry/Trip) is polymorphic, so searching by subject name
        // would need a per-type join; not attempted here, kept simple
        // and fast for what the table actually supports.
        $withSubject = fn ($query) => $query
            ->with('followupable', 'creator')
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $search = $request->string('q');
                $q->where('reason', 'like', "%{$search}%")->orWhere('notes', 'like', "%{$search}%");
            }))
            ->orderBy('due_at');

        // Each group paginates independently (distinct page-name params,
        // same pattern as the Money page's customer_page/supplier_page)
        // rather than loading every pending follow-up into memory.
        $overdue = $withSubject(FollowUp::query()->overdue())->paginate(15, ['*'], 'overdue_page')->withQueryString();
        $dueToday = $withSubject(FollowUp::query()->dueToday())->paginate(15, ['*'], 'today_page')->withQueryString();
        $upcoming = $withSubject(FollowUp::query()->upcoming())->paginate(15, ['*'], 'upcoming_page')->withQueryString();

        return view('follow_ups.index', compact('overdue', 'dueToday', 'upcoming'));
    }

    public function store(StoreFollowUpRequest $request): RedirectResponse
    {
        // Scoped find: 404s if the subject belongs to another company.
        $subject = $request->subjectClass()::findOrFail($request->integer('subject_id'));

        $followUp = $subject->followUps()->create([
            'due_at' => $request->date('due_at'),
            'reason' => $request->string('reason')->value() ?: null,
            'notes' => $request->string('notes')->value() ?: null,
        ]);

        return back()->with('status', 'Follow-up scheduled for '.$followUp->due_at->format('d M Y, g:i A').'.');
    }

    public function complete(FollowUp $followUp): RedirectResponse
    {
        $this->authorize('update', $followUp);

        $followUp->update([
            'status' => FollowUpStatus::Completed,
            'completed_at' => now(),
        ]);

        return back()->with('status', 'Follow-up marked complete.');
    }

    public function reschedule(RescheduleFollowUpRequest $request, FollowUp $followUp): RedirectResponse
    {
        $followUp->update([
            'due_at' => $request->date('due_at'),
            'notes' => $request->string('notes')->value() ?: $followUp->notes,
        ]);

        return back()->with('status', 'Follow-up rescheduled.');
    }

    public function cancel(FollowUp $followUp): RedirectResponse
    {
        $this->authorize('update', $followUp);

        $followUp->update(['status' => FollowUpStatus::Cancelled]);

        return back()->with('status', 'Follow-up cancelled.');
    }
}

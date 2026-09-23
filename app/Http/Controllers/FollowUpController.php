<?php

namespace App\Http\Controllers;

use App\Enums\FollowUpStatus;
use App\Http\Requests\FollowUp\RescheduleFollowUpRequest;
use App\Http\Requests\FollowUp\StoreFollowUpRequest;
use App\Models\FollowUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', FollowUp::class);

        $withSubject = fn ($query) => $query->with('followupable', 'creator')->orderBy('due_at');

        $overdue = $withSubject(FollowUp::query()->overdue())->get();
        $dueToday = $withSubject(FollowUp::query()->dueToday())->get();
        $upcoming = $withSubject(FollowUp::query()->upcoming())->get();

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

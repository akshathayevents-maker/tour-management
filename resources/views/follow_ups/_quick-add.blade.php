{{-- Reusable quick-add: drop into any entity's page with subjectType/subjectId set. --}}
<form method="POST" action="{{ route('follow-ups.store') }}" class="mb-3 space-y-2 text-sm">
    @csrf
    <input type="hidden" name="subject_type" value="{{ $subjectType }}">
    <input type="hidden" name="subject_id" value="{{ $subjectId }}">
    <div class="flex gap-2">
        <input type="datetime-local" name="due_at" required
               class="flex-1 rounded-md border-slate-300 shadow-sm text-sm">
        <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-3 py-1.5 hover:bg-slate-700">
            Schedule
        </button>
    </div>
    <input type="text" name="reason" placeholder="Reason (optional)"
           class="w-full rounded-md border-slate-300 shadow-sm text-sm">
</form>

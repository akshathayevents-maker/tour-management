@php $overdue = $followUp->isOverdue(); @endphp
<div class="flex items-start justify-between gap-3 py-2.5 border-b border-slate-50 last:border-0 text-sm">
    <div class="min-w-0">
        @if (! isset($hideSubject))
            <a href="{{ $followUp->subjectUrl() }}" class="text-slate-900 font-medium hover:text-brand-700 truncate block">
                {{ $followUp->subjectLabel() }}
            </a>
        @endif
        <p class="{{ $overdue ? 'text-red-600 font-medium' : 'text-slate-500' }} text-xs mt-0.5">
            {{ $followUp->due_at->format('d M Y, g:i A') }}
            @if ($overdue) &middot; Overdue @endif
        </p>
        @if ($followUp->reason)
            <p class="text-slate-400 text-xs mt-0.5 truncate">{{ $followUp->reason }}</p>
        @endif
    </div>
    <form method="POST" action="{{ route('follow-ups.complete', $followUp) }}" class="shrink-0">
        @csrf @method('PATCH')
        <button class="text-xs font-medium text-emerald-700 hover:text-emerald-800 rounded-md border border-slate-200 px-2 py-1 hover:bg-emerald-50">Complete</button>
    </form>
</div>

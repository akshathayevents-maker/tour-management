@php $overdue = $followUp->isOverdue(); @endphp
<div class="py-2 border-b border-slate-50 last:border-0 text-sm">
    <div class="flex justify-between items-start gap-2">
        <div>
            @if (! isset($hideSubject))
                <a href="{{ $followUp->subjectUrl() }}" class="text-slate-900 font-medium hover:underline">
                    {{ $followUp->subjectLabel() }}
                </a>
            @endif
            <p class="{{ $overdue ? 'text-red-600' : 'text-slate-500' }}">
                {{ $followUp->due_at->format('d M Y, g:i A') }}
                @if ($overdue) &middot; Overdue @endif
            </p>
            @if ($followUp->reason)
                <p class="text-slate-400">{{ $followUp->reason }}</p>
            @endif
        </div>
        <div class="flex gap-2 shrink-0">
            <form method="POST" action="{{ route('follow-ups.complete', $followUp) }}">
                @csrf @method('PATCH')
                <button class="text-green-700 hover:underline">Complete</button>
            </form>
        </div>
    </div>
</div>

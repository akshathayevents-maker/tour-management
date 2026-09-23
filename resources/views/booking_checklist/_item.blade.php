{{-- Expects $item (BookingChecklistItem) --}}
<div class="flex items-center justify-between gap-2 py-1.5 border-b border-slate-50 last:border-0 text-sm">
    <div class="flex items-center gap-2 min-w-0">
        <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $item->isDone() ? 'bg-emerald-100 text-emerald-700' : 'border border-slate-300' }}">
            @if ($item->isDone())
                <svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
            @endif
        </span>
        <span class="{{ $item->isDone() ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $item->label }}</span>
        @if ($item->isDone())
            <span class="text-xs text-slate-400">{{ $item->done_at->format('d M') }}@if($item->doneBy) &middot; {{ $item->doneBy->name }} @endif</span>
        @endif
    </div>
    @unless ($item->isDone())
        <form method="POST" action="{{ route('booking-checklist-items.complete', $item) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="text-xs text-green-700 hover:underline shrink-0">Complete</button>
        </form>
    @endunless
</div>

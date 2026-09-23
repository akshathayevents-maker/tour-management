{{-- Expects $item (BookingChecklistItem) --}}
<div class="flex items-center justify-between gap-2 py-1.5 border-b border-slate-50 last:border-0 text-sm">
    <div class="flex items-center gap-2 min-w-0">
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

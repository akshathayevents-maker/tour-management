{{-- Expects $item (ItineraryItem) --}}
<div class="flex items-start justify-between gap-2 py-1.5 border-b border-slate-50 last:border-0 text-sm">
    <div class="flex-1 min-w-0">
        @if ($item->time)
            <span class="text-slate-400 mr-2 tabular-nums">{{ \Illuminate\Support\Carbon::parse($item->time)->format('g:i A') }}</span>
        @endif
        <span class="text-slate-900">{{ $item->title }}</span>
        @if ($item->notes)
            <p class="text-xs text-slate-400">{{ $item->notes }}</p>
        @endif
    </div>
    <div class="flex items-center gap-1 shrink-0 text-slate-400">
        <form method="POST" action="{{ route('itinerary-items.move', $item) }}">
            @csrf
            <input type="hidden" name="direction" value="up">
            <button type="submit" class="px-1 hover:text-slate-900" aria-label="Move up">&uarr;</button>
        </form>
        <form method="POST" action="{{ route('itinerary-items.move', $item) }}">
            @csrf
            <input type="hidden" name="direction" value="down">
            <button type="submit" class="px-1 hover:text-slate-900" aria-label="Move down">&darr;</button>
        </form>
        <details class="relative">
            <summary class="list-none cursor-pointer px-1 hover:text-slate-900">Edit</summary>
            <form method="POST" action="{{ route('itinerary-items.update', $item) }}"
                  class="absolute right-0 mt-1 w-56 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                @csrf
                @method('PATCH')
                <input name="title" value="{{ $item->title }}" required
                       class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                <input type="time" name="time" value="{{ $item->time }}"
                       class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                <input name="notes" value="{{ $item->notes }}" placeholder="Notes (optional)"
                       class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                <button type="submit" class="w-full rounded-md bg-brand-700 text-white text-xs font-medium px-3 py-1.5 hover:bg-brand-800">
                    Save
                </button>
            </form>
        </details>
        <form method="POST" action="{{ route('itinerary-items.destroy', $item) }}"
              onsubmit="return confirm('Remove this item?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-1 hover:text-red-600" aria-label="Delete">&times;</button>
        </form>
    </div>
</div>

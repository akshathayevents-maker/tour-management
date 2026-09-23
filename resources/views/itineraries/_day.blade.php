{{-- Expects $day (ItineraryDay with items loaded) --}}
<div class="border-t border-slate-100 pt-3 mt-3 first:border-0 first:pt-0 first:mt-0">
    <div class="flex items-center justify-between mb-1">
        <h4 class="text-sm font-medium text-slate-900">
            Day {{ $day->day_number }}
            @if ($day->date) <span class="text-slate-400 font-normal">&middot; {{ $day->date->format('d M Y') }}</span> @endif
        </h4>
        <form method="POST" action="{{ route('itinerary-days.destroy', $day) }}" onsubmit="return confirm('Remove this day and its items?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-slate-400 hover:text-red-600">Remove day</button>
        </form>
    </div>

    @if ($day->notes)
        <p class="text-xs text-slate-400 mb-1">{{ $day->notes }}</p>
    @endif

    @foreach ($day->items as $item)
        @include('itineraries._item', ['item' => $item])
    @endforeach

    <form method="POST" action="{{ route('itinerary-items.store', $day) }}" class="flex gap-2 mt-2">
        @csrf
        <input type="text" name="title" placeholder="Add an activity…" required
               class="flex-1 rounded-md border-slate-300 shadow-sm text-sm">
        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
            Add
        </button>
    </form>
</div>

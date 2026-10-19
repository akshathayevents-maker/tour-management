{{-- Expects $day (ItineraryDay with items loaded) --}}
<div class="flex gap-3 border-t border-slate-100 pt-4 mt-4 first:border-0 first:pt-0 first:mt-0">
    <div class="shrink-0 flex flex-col items-center">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-xs font-semibold">{{ $day->day_number }}</span>
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2 mb-1">
            <h4 class="text-sm font-semibold text-slate-900 min-w-0">
                Day {{ $day->day_number }}
                @if ($day->date) <span class="text-slate-400 font-normal">&middot; {{ $day->date->format('d M Y') }}</span> @endif
                @if ($day->items->isNotEmpty())
                    <span class="text-xs text-slate-400 font-normal">&middot; {{ $day->items->count() }} {{ Str::plural('activity', $day->items->count()) }}</span>
                @endif
            </h4>
            <div class="flex items-center gap-2 shrink-0 text-xs">
                <details class="relative">
                    <summary class="list-none cursor-pointer text-slate-400 hover:text-slate-900">Edit</summary>
                    <form method="POST" action="{{ route('itinerary-days.update', $day) }}"
                          class="absolute right-0 mt-1 w-64 bg-white rounded-md shadow-lg border border-slate-200 p-3 z-10 space-y-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="day_number" value="{{ $day->day_number }}">
                        <label class="block text-xs text-slate-500">Date (optional)</label>
                        <input type="date" name="date" value="{{ $day->date?->format('Y-m-d') }}"
                               class="block w-full rounded-md border-slate-300 shadow-sm text-sm">
                        <label class="block text-xs text-slate-500">Notes (optional)</label>
                        <textarea name="notes" rows="2" class="block w-full rounded-md border-slate-300 shadow-sm text-sm">{{ $day->notes }}</textarea>
                        <button type="submit" class="w-full rounded-md bg-brand-700 text-white text-xs font-medium px-3 py-1.5 hover:bg-brand-800">
                            Save
                        </button>
                    </form>
                </details>
                <form method="POST" action="{{ route('itinerary-days.destroy', $day) }}" onsubmit="return confirm('Remove this day and its items?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-slate-400 hover:text-red-600">Remove day</button>
                </form>
            </div>
        </div>

        @if ($day->notes)
            <p class="text-xs text-slate-500 mb-1.5">{{ $day->notes }}</p>
        @endif

        @forelse ($day->items as $item)
            @include('itineraries._item', ['item' => $item])
        @empty
            <p class="text-xs text-slate-400 py-1">No activities planned for this day yet.</p>
        @endforelse

        <form method="POST" action="{{ route('itinerary-items.store', $day) }}" class="flex gap-2 mt-2">
            @csrf
            <input type="text" name="title" placeholder="Add an activity…" required
                   class="flex-1 min-w-0 rounded-md border-slate-300 shadow-sm text-sm">
            <button type="submit" class="shrink-0 rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
                Add
            </button>
        </form>
    </div>
</div>

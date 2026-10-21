{{-- Expects: $action (route), $method ('POST'|'PATCH'), optional $template (ItineraryTemplate with days.items loaded) --}}
<form method="POST" action="{{ $action }}" id="template-form">
    @csrf
    @if ($method === 'PATCH')
        @method('PATCH')
    @endif

    <div class="bg-white rounded-xl border border-slate-200/80 px-5 sm:px-7 py-5 mb-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Template details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Destination <span class="form-required">*</span></label>
                <input type="text" name="destination" required value="{{ old('destination', $template->destination ?? '') }}" class="form-input">
                @error('destination')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Template name <span class="form-required">*</span></label>
                <input type="text" name="name" required value="{{ old('name', $template->name ?? '') }}" placeholder="e.g. Relaxed 3-day" class="form-input">
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-input">{{ old('description', $template->description ?? '') }}</textarea>
                @error('description')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active ?? true)) class="rounded border-slate-300">
                Active (selectable in the itinerary builder)
            </label>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 px-5 sm:px-7 py-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-900">Days &amp; activities</h3>
        </div>

        <div id="days"></div>

        <button type="button" id="add-day" class="mt-2 rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
            + Add day
        </button>
        @error('days')<p class="form-error">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center justify-end gap-2 mt-5">
        <x-button tag="a" href="{{ route('itinerary-templates.index') }}" variant="secondary">Cancel</x-button>
        <x-button type="submit">Save template</x-button>
    </div>
</form>

<template id="day-template">
    <div class="day-row rounded-lg border border-slate-200 p-4 mb-3">
        <div class="flex items-start justify-between gap-2 mb-3">
            <span class="day-index text-sm font-semibold text-slate-900"></span>
            <button type="button" class="remove-day text-xs text-slate-400 hover:text-red-600">Remove day</button>
        </div>
        <input type="hidden" class="day-number-input" name="days[__DAY__][day_number]" value="1">
        <div class="mb-3">
            <label class="form-label">Day notes (optional)</label>
            <input type="text" name="days[__DAY__][notes]" placeholder="e.g. Travel day, light sightseeing" class="form-input">
        </div>
        <div class="items-container space-y-2"></div>
        <button type="button" class="add-item mt-2 text-xs rounded-md border border-slate-300 px-2.5 py-1.5 text-slate-600 hover:bg-slate-50">
            + Add activity
        </button>
    </div>
</template>

<template id="item-template">
    <div class="item-row grid grid-cols-1 sm:grid-cols-5 gap-2 items-start">
        <input type="text" name="days[__DAY__][items][__ITEM__][title]" placeholder="Activity title" required class="sm:col-span-2 form-input">
        <input type="time" name="days[__DAY__][items][__ITEM__][time]" class="form-input">
        <input type="text" name="days[__DAY__][items][__ITEM__][notes]" placeholder="Notes (optional)" class="sm:col-span-2 form-input">
        <button type="button" class="remove-item text-xs text-slate-400 hover:text-red-600 sm:col-span-5 justify-self-start">Remove activity</button>
    </div>
</template>

<script>
    (function () {
        const daysContainer = document.getElementById('days');
        const dayTemplate = document.getElementById('day-template');
        const itemTemplate = document.getElementById('item-template');
        let dayIndex = 0;

        function renumberDays() {
            daysContainer.querySelectorAll('.day-row').forEach((row, i) => {
                row.querySelector('.day-index').textContent = 'Day ' + (i + 1);
                row.querySelector('.day-number-input').value = i + 1;
            });
        }

        function addItem(itemsContainer, dayId) {
            const itemIndex = itemsContainer.children.length;
            const node = itemTemplate.content.cloneNode(true);
            node.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace('__DAY__', dayId).replace('__ITEM__', itemIndex);
            });
            itemsContainer.appendChild(node);
        }

        function addDay(prefill) {
            const node = dayTemplate.content.cloneNode(true);
            const dayId = dayIndex++;
            node.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace('__DAY__', dayId);
            });
            daysContainer.appendChild(node);

            const addedRow = daysContainer.lastElementChild;
            const itemsContainer = addedRow.querySelector('.items-container');

            addedRow.querySelector('.add-item').addEventListener('click', () => addItem(itemsContainer, dayId));
            addedRow.querySelector('.remove-day').addEventListener('click', () => {
                addedRow.remove();
                renumberDays();
            });
            itemsContainer.addEventListener('click', (event) => {
                if (event.target.classList.contains('remove-item')) {
                    event.target.closest('.item-row').remove();
                }
            });

            if (prefill) {
                if (prefill.notes) addedRow.querySelector('input[name$="[notes]"]').value = prefill.notes;
                (prefill.items || []).forEach((item) => {
                    addItem(itemsContainer, dayId);
                    const lastItem = itemsContainer.lastElementChild;
                    lastItem.querySelector('input[name$="[title]"]').value = item.title || '';
                    lastItem.querySelector('input[name$="[time]"]').value = item.time || '';
                    lastItem.querySelector('input[name$="[notes]"]').value = item.notes || '';
                });
            }

            renumberDays();
        }

        document.getElementById('add-day').addEventListener('click', () => addDay(null));

        @php
            $existingDaysPayload = ($template ?? null)
                ? $template->days->map(fn ($day) => [
                    'notes' => $day->notes,
                    'items' => $day->items->map(fn ($item) => [
                        'title' => $item->title,
                        'time' => $item->time ? \Illuminate\Support\Carbon::parse($item->time)->format('H:i') : null,
                        'notes' => $item->notes,
                    ])->all(),
                ])->all()
                : [];
        @endphp
        const existingDays = @json($existingDaysPayload);

        if (existingDays.length > 0) {
            existingDays.forEach((day) => addDay(day));
        } else {
            addDay(null);
        }
    })();
</script>

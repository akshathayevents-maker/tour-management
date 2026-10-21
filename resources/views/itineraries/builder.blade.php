@extends('layouts.app')

@section('title', 'Build itinerary · '.$trip->destination)

@section('content')
    <a href="{{ route('trips.show', $trip) }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-700 mb-3">
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" /></svg>
        {{ $trip->destination }}
    </a>

    <div class="mb-5">
        <p class="text-[11px] font-semibold uppercase tracking-widest text-accent-500 mb-1">Itinerary builder</p>
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Build a multi-destination itinerary</h2>
        <p class="mt-0.5 text-sm text-slate-500 max-w-lg">
            Add each destination in travel order, choose how many days to spend there, and optionally pre-fill it from a reusable template.
        </p>
    </div>

    @if ($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @php
        $existingDays = $trip->itinerary?->days ?? collect();
        $existingActivityCount = $existingDays->sum(fn ($day) => $day->items->count());
        $existingDestinations = $existingDays->pluck('destination')->filter()->unique()->values();
    @endphp
    @if ($existingDays->isNotEmpty())
        <x-alert type="warning">
            <p>
                This trip already has a saved itinerary —
                <strong>{{ $existingDays->count() }} {{ Str::plural('day', $existingDays->count()) }}</strong>
                and <strong>{{ $existingActivityCount }} {{ Str::plural('activity', $existingActivityCount) }}</strong>,
                including any you've manually added or edited.
            </p>
            @if ($existingDestinations->isNotEmpty())
                <p class="mt-1 text-xs">Currently covers: {{ $existingDestinations->join(', ') }}.</p>
            @endif
            <p class="mt-2">
                Generating a new itinerary below will <strong>permanently replace all of it</strong> — this cannot be undone.
            </p>
        </x-alert>
    @endif

    @if ($templates->isEmpty())
        <x-alert type="info">
            No itinerary templates exist yet. You can still generate a blank day-by-day structure for each destination, or
            <a href="{{ route('itinerary-templates.create') }}" class="underline font-medium">create a template</a> first.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('trips.itinerary-builder.store', $trip) }}" id="builder-form"
          oninput="document.getElementById('total-days').textContent = Array.from(document.querySelectorAll('.segment-days')).reduce((sum, el) => sum + (parseInt(el.value, 10) || 0), 0);">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200/80 px-5 sm:px-7 py-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-900">Destinations</h3>
                <p class="text-sm text-slate-500">Total: <span id="total-days" class="font-semibold text-slate-900">0</span> days</p>
            </div>

            <div id="segments"></div>

            <button type="button" id="add-segment"
                    class="mt-2 rounded-md bg-white border border-slate-300 text-slate-700 text-sm font-medium px-3 py-1.5 hover:bg-slate-50">
                + Add destination
            </button>

            @if ($existingDays->isNotEmpty())
                <label class="flex items-start gap-2 mt-5 pt-4 border-t border-slate-100 text-sm text-slate-700">
                    <input type="checkbox" name="confirm_replace" value="1" required class="mt-0.5 rounded border-slate-300">
                    I understand this will replace the existing {{ $existingDays->count() }}-day itinerary.
                </label>
            @endif
        </div>

        <div class="flex items-center justify-end gap-2 mt-5">
            <x-button tag="a" href="{{ route('trips.show', $trip) }}" variant="secondary">Cancel</x-button>
            <x-button type="submit" id="generate-button" disabled>Generate itinerary</x-button>
        </div>
    </form>

    {{-- Row template, cloned by JS — never rendered directly. --}}
    <template id="segment-template">
        <div class="segment-row rounded-lg border border-slate-200 p-4 mb-3">
            <div class="flex items-start justify-between gap-2 mb-3">
                <span class="segment-index text-xs font-semibold text-slate-400 uppercase tracking-wide pt-2"></span>
                <button type="button" class="remove-segment text-xs text-slate-400 hover:text-red-600">Remove</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="form-label">Destination <span class="form-required">*</span></label>
                    <input type="text" name="segments[__INDEX__][destination]" required placeholder="e.g. Ooty" class="form-input">
                </div>
                <div>
                    <label class="form-label">Days <span class="form-required">*</span></label>
                    <input type="number" min="1" max="60" name="segments[__INDEX__][days]" required value="1" class="form-input segment-days">
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label">Template (optional)</label>
                <select name="segments[__INDEX__][itinerary_template_id]" class="form-select">
                    <option value="">No template — blank days</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}">
                            {{ $template->destination }} &middot; {{ $template->name }} &middot; {{ $template->days_count }} {{ Str::plural('day', $template->days_count) }}
                        </option>
                    @endforeach
                </select>
                <p class="form-help">
                    If the template's day count doesn't match the days entered above, extra days are left blank and any unused template days are simply not used — nothing is invented or silently dropped within a day.
                </p>
            </div>
        </div>
    </template>

    <script>
        (function () {
            const container = document.getElementById('segments');
            const rowTemplate = document.getElementById('segment-template');
            const generateButton = document.getElementById('generate-button');
            let index = 0;

            function renumber() {
                container.querySelectorAll('.segment-row').forEach((row, i) => {
                    row.querySelector('.segment-index').textContent = 'Destination ' + (i + 1);
                });
                generateButton.disabled = container.children.length === 0;
            }

            function addSegment() {
                const node = rowTemplate.content.cloneNode(true);
                node.querySelectorAll('[name]').forEach((el) => {
                    el.name = el.name.replace('__INDEX__', index);
                });
                index++;
                container.appendChild(node);
                renumber();
            }

            container.addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-segment')) {
                    event.target.closest('.segment-row').remove();
                    renumber();
                    document.getElementById('builder-form').dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            document.getElementById('add-segment').addEventListener('click', addSegment);

            // Start with one row so the form is usable immediately.
            addSegment();
        })();
    </script>
@endsection

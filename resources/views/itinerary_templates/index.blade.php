@extends('layouts.app')

@section('title', 'Itinerary templates')

@section('content')
    <x-page-header title="Itinerary templates" subtitle="Reusable, destination-specific day-by-day plans for the itinerary builder.">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('itinerary-templates.create') }}">+ New template</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-5 flex flex-wrap items-center gap-2 bg-white border border-slate-200/80 rounded-xl px-3 py-2.5">
        <div class="relative flex-1 min-w-[220px]">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search templates by name or destination…"
                   aria-label="Search templates by name or destination"
                   class="w-full rounded-md border-slate-300 shadow-sm text-sm">
        </div>
        <button type="submit" class="rounded-md bg-white border border-slate-300 text-slate-600 text-[13px] font-medium px-3 py-1.5 hover:bg-slate-50">Search</button>
        @if (request()->filled('q'))
            <a href="{{ route('itinerary-templates.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Clear</a>
        @endif
    </form>

    @if ($templates->isEmpty())
        <x-empty-state
            title="No itinerary templates yet."
            description="Create reusable day-by-day plans per destination so staff can generate trip itineraries in seconds."
            actionLabel="Create a template"
            :actionUrl="route('itinerary-templates.create')" />
    @else
        <div class="bg-white rounded-xl border border-slate-200/80 divide-y divide-slate-100">
            @foreach ($templates as $template)
                <div class="flex items-center gap-4 px-4 py-3.5">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 truncate">{{ $template->name }}</p>
                        <p class="text-xs text-slate-400">{{ $template->destination }} &middot; {{ $template->days_count }} {{ Str::plural('day', $template->days_count) }}</p>
                    </div>
                    <x-status-badge :color="$template->is_active ? 'green' : 'slate'" :label="$template->is_active ? 'Active' : 'Inactive'" />
                    <a href="{{ route('itinerary-templates.edit', $template) }}" class="text-sm font-medium text-brand-700 hover:underline">Edit</a>
                    <form method="POST" action="{{ route('itinerary-templates.toggle-active', $template) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-sm text-slate-500 hover:text-slate-900">
                            {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('itinerary-templates.destroy', $template) }}" onsubmit="return confirm('Delete this template? Trips already generated from it are not affected.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-slate-400 hover:text-red-600">Delete</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $templates->links() }}</div>
    @endif
@endsection

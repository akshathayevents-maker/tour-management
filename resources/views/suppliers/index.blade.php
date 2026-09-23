@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
    <x-page-header title="Suppliers" subtitle="Hotels, transport, activities and everyone else you pay.">
        <x-slot:actions>
            <a href="{{ route('suppliers.create') }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Add supplier
            </a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name…"
               class="w-full sm:w-64 rounded-md border-slate-300 shadow-sm sm:text-sm">
        <select name="type" onchange="this.form.submit()" class="rounded-md border-slate-300 shadow-sm sm:text-sm">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </form>

    @if ($suppliers->isEmpty())
        <x-empty-state
            title="No suppliers yet."
            description="Add hotels, drivers or activity providers as you start pricing trips."
            action-label="Add supplier"
            :action-url="route('suppliers.create')" />
    @else
        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Type</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Phone</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($suppliers as $supplier)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('suppliers.show', $supplier) }}" class="text-slate-900 font-medium hover:underline">
                                    {{ $supplier->name }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-slate-500">{{ $supplier->type?->label() ?? '—' }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ $supplier->phone ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <x-status-badge :color="$supplier->is_active ? 'green' : 'slate'" :label="$supplier->is_active ? 'Active' : 'Inactive'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $suppliers->links() }}</div>
    @endif
@endsection

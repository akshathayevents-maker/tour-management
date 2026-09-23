@extends('layouts.app')

@section('title', 'New trip')

@section('content')
    <x-page-header title="New trip" subtitle="Just the destination is enough to get started." />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('trips.store') }}" class="space-y-4">
            @csrf

            @if ($enquiry)
                <input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">
                <p class="text-xs text-slate-500">Planning from <a href="{{ route('enquiries.show', $enquiry) }}" class="underline">this enquiry</a>.</p>
            @endif

            <div>
                <label for="customer_id" class="block text-sm font-medium text-slate-700">Customer</label>
                @if ($customer)
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <p class="mt-1 text-sm text-slate-900">{{ $customer->name }} &middot; {{ $customer->phone }}</p>
                @else
                    <select id="customer_id" name="customer_id" required
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        <option value="">Select a customer</option>
                        @foreach (\App\Models\Customer::orderBy('name')->get() as $option)
                            <option value="{{ $option->id }}" @selected(old('customer_id') == $option->id)>
                                {{ $option->name }} &middot; {{ $option->phone }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Don't see them? <a href="{{ route('customers.create') }}" class="underline">Add a customer first</a>.</p>
                @endif
            </div>

            <div>
                <label for="destination" class="block text-sm font-medium text-slate-700">Destination</label>
                <input id="destination" name="destination" value="{{ old('destination', $enquiry->destination ?? '') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>

            <details class="text-sm" @if(old('start_date') || old('end_date') || old('travellers_count')) open @endif>
                <summary class="cursor-pointer text-slate-500">+ Add more details (optional)</summary>
                <div class="mt-3 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-slate-700">Start date</label>
                            <input id="start_date" type="date" name="start_date"
                                   value="{{ old('start_date', optional($enquiry->start_date ?? null)->format('Y-m-d')) }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-slate-700">End date</label>
                            <input id="end_date" type="date" name="end_date"
                                   value="{{ old('end_date', optional($enquiry->end_date ?? null)->format('Y-m-d')) }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="travellers_count" class="block text-sm font-medium text-slate-700">Traveller count</label>
                        <input id="travellers_count" type="number" min="1" name="travellers_count"
                               value="{{ old('travellers_count', ($enquiry->adults ?? 0) + ($enquiry->children ?? 0) ?: '') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    </div>
                </div>
            </details>

            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save trip
            </button>
        </form>
    </div>
@endsection

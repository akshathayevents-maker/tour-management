@extends('layouts.app')

@section('title', 'New booking')

@section('content')
    <x-page-header title="New booking" subtitle="For a repeat customer, phone or WhatsApp booking — no quotation required." />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('bookings.store') }}" class="space-y-4">
            @csrf

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
                @endif
            </div>

            <div>
                <label for="trip_id" class="block text-sm font-medium text-slate-700">Trip</label>
                @if ($trip)
                    <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                    <p class="mt-1 text-sm text-slate-900">{{ $trip->destination }}</p>
                @else
                    <select id="trip_id" name="trip_id" required
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        <option value="">Select a trip</option>
                        @foreach (\App\Models\Trip::orderByDesc('id')->get() as $option)
                            <option value="{{ $option->id }}" @selected(old('trip_id') == $option->id)>
                                {{ $option->destination }} &middot; {{ $option->customer->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Don't see it? <a href="{{ route('trips.create') }}" class="underline">Create a trip first</a>.</p>
                @endif
            </div>

            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save booking
            </button>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Add enquiry')

@section('content')
    <x-page-header title="Add enquiry" subtitle="Just the customer and destination is enough to start." />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('enquiries.store') }}" class="space-y-4">
            @csrf

            @if ($customer)
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <div>
                    <span class="block text-sm font-medium text-slate-700">Customer</span>
                    <p class="mt-1 text-sm text-slate-900">{{ $customer->name }} &middot; {{ $customer->phone }}</p>
                </div>
            @else
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-slate-700">Customer</label>
                    <select id="customer_id" name="customer_id" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        <option value="">Select a customer</option>
                        @foreach (\App\Models\Customer::orderBy('name')->get() as $option)
                            <option value="{{ $option->id }}" @selected(old('customer_id') == $option->id)>
                                {{ $option->name }} &middot; {{ $option->phone }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Don't see them? <a href="{{ route('customers.create') }}" class="underline">Add a customer first</a>.</p>
                </div>
            @endif

            <div>
                <label for="destination" class="block text-sm font-medium text-slate-700">Destination</label>
                <input id="destination" name="destination" value="{{ old('destination') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>

            <details class="text-sm">
                <summary class="cursor-pointer text-slate-500">+ Add more details (optional)</summary>
                <div class="mt-3 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-slate-700">Start date</label>
                            <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-slate-700">End date</label>
                            <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="adults" class="block text-sm font-medium text-slate-700">Adults</label>
                            <input id="adults" name="adults" type="number" min="0" value="{{ old('adults') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label for="children" class="block text-sm font-medium text-slate-700">Children</label>
                            <input id="children" name="children" type="number" min="0" value="{{ old('children') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="budget" class="block text-sm font-medium text-slate-700">Budget</label>
                        <input id="budget" name="budget" type="number" step="0.01" min="0" value="{{ old('budget') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label for="hotel_category" class="block text-sm font-medium text-slate-700">Hotel category</label>
                        <input id="hotel_category" name="hotel_category" value="{{ old('hotel_category') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label for="meal_plan" class="block text-sm font-medium text-slate-700">Meal plan</label>
                        <input id="meal_plan" name="meal_plan" value="{{ old('meal_plan') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="transport_required" value="1" class="rounded border-slate-300">
                        Transport required
                    </label>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </details>

            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save enquiry
            </button>
        </form>
    </div>
@endsection

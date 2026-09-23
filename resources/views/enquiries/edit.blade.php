@extends('layouts.app')

@section('title', 'Edit enquiry')

@section('content')
    <x-page-header title="Edit enquiry" subtitle="For {{ $enquiry->customer->name }}" />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('enquiries.update', $enquiry) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="destination" class="block text-sm font-medium text-slate-700">Destination</label>
                <input id="destination" name="destination" value="{{ old('destination', $enquiry->destination) }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    @foreach (\App\Enums\EnquiryStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $enquiry->status->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-slate-700">Start date</label>
                    <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $enquiry->start_date?->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-slate-700">End date</label>
                    <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $enquiry->end_date?->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="adults" class="block text-sm font-medium text-slate-700">Adults</label>
                    <input id="adults" name="adults" type="number" min="0" value="{{ old('adults', $enquiry->adults) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label for="children" class="block text-sm font-medium text-slate-700">Children</label>
                    <input id="children" name="children" type="number" min="0" value="{{ old('children', $enquiry->children) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
            </div>
            <div>
                <label for="budget" class="block text-sm font-medium text-slate-700">Budget</label>
                <input id="budget" name="budget" type="number" step="0.01" min="0" value="{{ old('budget', $enquiry->budget) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="hotel_category" class="block text-sm font-medium text-slate-700">Hotel category</label>
                <input id="hotel_category" name="hotel_category" value="{{ old('hotel_category', $enquiry->hotel_category) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="meal_plan" class="block text-sm font-medium text-slate-700">Meal plan</label>
                <input id="meal_plan" name="meal_plan" value="{{ old('meal_plan', $enquiry->meal_plan) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="transport_required" value="1" @checked(old('transport_required', $enquiry->transport_required)) class="rounded border-slate-300">
                Transport required
            </label>
            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes', $enquiry->notes) }}</textarea>
            </div>
            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save changes
            </button>
        </form>
    </div>
@endsection

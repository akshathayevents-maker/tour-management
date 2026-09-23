@extends('layouts.app')

@section('title', 'Edit enquiry')

@section('content')
    <x-page-header title="Edit enquiry" subtitle="For {{ $enquiry->customer->name }}" />

    <div class="max-w-3xl bg-white rounded-lg border border-slate-200 px-6">
        <form method="POST" action="{{ route('enquiries.update', $enquiry) }}">
            @csrf
            @method('PUT')

            <x-form-section title="Destination & status">
                <x-field label="Destination" name="destination" required>
                    <input id="destination" name="destination" value="{{ old('destination', $enquiry->destination) }}" required autofocus
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Status" name="status" required>
                    <select id="status" name="status" required class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        @foreach (\App\Enums\EnquiryStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $enquiry->status->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
            </x-form-section>

            <x-form-section title="Travel details">
                <x-field label="Start date" name="start_date">
                    <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $enquiry->start_date?->format('Y-m-d')) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="End date" name="end_date">
                    <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $enquiry->end_date?->format('Y-m-d')) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Adults" name="adults">
                    <input id="adults" name="adults" type="number" min="0" value="{{ old('adults', $enquiry->adults) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Children" name="children">
                    <input id="children" name="children" type="number" min="0" value="{{ old('children', $enquiry->children) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Budget" name="budget">
                    <input id="budget" name="budget" type="number" step="0.01" min="0" value="{{ old('budget', $enquiry->budget) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Hotel category" name="hotel_category">
                    <input id="hotel_category" name="hotel_category" value="{{ old('hotel_category', $enquiry->hotel_category) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Meal plan" name="meal_plan">
                    <input id="meal_plan" name="meal_plan" value="{{ old('meal_plan', $enquiry->meal_plan) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Transport" name="transport_required">
                    <label class="flex items-center gap-2 text-sm text-slate-700 py-1.5">
                        <input type="checkbox" name="transport_required" value="1" @checked(old('transport_required', $enquiry->transport_required)) class="rounded border-slate-300">
                        Required
                    </label>
                </x-field>
                <x-field label="Notes" name="notes" wide>
                    <textarea id="notes" name="notes" rows="3"
                              class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes', $enquiry->notes) }}</textarea>
                </x-field>
            </x-form-section>

            <div class="flex items-center justify-end gap-2 py-4 border-t border-slate-100">
                <x-button tag="a" href="{{ route('enquiries.show', $enquiry) }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </form>
    </div>
@endsection

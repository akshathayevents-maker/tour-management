@extends('layouts.app')

@section('title', 'Edit customer')

@section('content')
    <x-page-header title="Edit customer" subtitle="{{ $customer->name }}" />

    <div class="max-w-3xl bg-white rounded-lg border border-slate-200 px-6">
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')

            <x-form-section title="Identity">
                <x-field label="Name" name="name" required>
                    <input id="name" name="name" value="{{ old('name', $customer->name) }}" required autofocus
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Phone" name="phone" required>
                    <input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
            </x-form-section>

            <x-form-section title="Additional details">
                <x-field label="Email" name="email">
                    <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="WhatsApp number" name="whatsapp">
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="City" name="city">
                    <input id="city" name="city" value="{{ old('city', $customer->city) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Country" name="country">
                    <input id="country" name="country" value="{{ old('country', $customer->country) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Notes" name="notes" wide>
                    <textarea id="notes" name="notes" rows="3"
                              class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes', $customer->notes) }}</textarea>
                </x-field>
            </x-form-section>

            <div class="flex items-center justify-end gap-2 py-4 border-t border-slate-100">
                <x-button tag="a" href="{{ route('customers.show', $customer) }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </form>
    </div>
@endsection

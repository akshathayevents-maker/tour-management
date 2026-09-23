@extends('layouts.app')

@section('title', 'Edit customer')

@section('content')
    <x-page-header title="Edit customer" />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" value="{{ old('name', $customer->name) }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="whatsapp" class="block text-sm font-medium text-slate-700">WhatsApp number</label>
                <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="city" class="block text-sm font-medium text-slate-700">City</label>
                    <input id="city" name="city" value="{{ old('city', $customer->city) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label for="country" class="block text-sm font-medium text-slate-700">Country</label>
                    <input id="country" name="country" value="{{ old('country', $customer->country) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </div>
            </div>
            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes', $customer->notes) }}</textarea>
            </div>
            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save changes
            </button>
        </form>
    </div>
@endsection

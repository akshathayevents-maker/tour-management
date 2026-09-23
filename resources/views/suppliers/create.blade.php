@extends('layouts.app')

@section('title', 'Add supplier')

@section('content')
    <x-page-header title="Add supplier" subtitle="Just a name is enough to get started." />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-slate-700">Type</label>
                <select id="type" name="type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    <option value="">Select type (optional)</option>
                    @foreach (\App\Enums\SupplierType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <details class="text-sm">
                <summary class="cursor-pointer text-slate-500">+ Add more details (optional)</summary>
                <div class="mt-3 space-y-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-slate-700">WhatsApp</label>
                        <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label for="payment_terms" class="block text-sm font-medium text-slate-700">Payment terms</label>
                        <input id="payment_terms" name="payment_terms" value="{{ old('payment_terms') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </details>

            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save supplier
            </button>
        </form>
    </div>
@endsection

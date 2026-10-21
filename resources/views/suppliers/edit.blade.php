@extends('layouts.app')

@section('title', 'Edit supplier')

@section('content')
    <x-page-header title="Edit supplier" subtitle="{{ $supplier->name }}" />

    <div class="max-w-3xl bg-white rounded-lg border border-slate-200 px-6">
        <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')

            <x-form-section title="Supplier profile">
                <x-field label="Supplier name" name="name" required wide>
                    <input id="name" name="name" value="{{ old('name', $supplier->name) }}" required autofocus
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Type" name="type">
                    <select id="type" name="type" class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        <option value="">Select type (optional)</option>
                        @foreach (\App\Enums\SupplierType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('type', $supplier->type?->value) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Status" name="is_active">
                    <label class="flex items-center gap-2 text-sm text-slate-700 py-2.5">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active)) class="rounded border-slate-300">
                        Active — available to book
                    </label>
                </x-field>
            </x-form-section>

            <x-form-section title="Contact details">
                <x-field label="Phone" name="phone">
                    <input id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}" type="tel"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="WhatsApp" name="whatsapp">
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $supplier->whatsapp) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Email" name="email" wide>
                    <input id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
            </x-form-section>

            <x-form-section title="Business details">
                <x-field label="Payment terms" name="payment_terms" wide>
                    <input id="payment_terms" name="payment_terms" value="{{ old('payment_terms', $supplier->payment_terms) }}"
                           placeholder="e.g. 50% advance, balance on arrival"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Notes" name="notes" wide>
                    <textarea id="notes" name="notes" rows="3"
                              class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes', $supplier->notes) }}</textarea>
                </x-field>
            </x-form-section>

            <div class="flex items-center justify-end gap-2 py-4 border-t border-slate-100">
                <x-button tag="a" href="{{ route('suppliers.show', $supplier) }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Edit supplier')

@section('content')
    <x-page-header title="Edit supplier" />

    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" value="{{ old('name', $supplier->name) }}" required
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-slate-700">Type</label>
                <select id="type" name="type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                    <option value="">Select type (optional)</option>
                    @foreach (\App\Enums\SupplierType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $supplier->type?->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="whatsapp" class="block text-sm font-medium text-slate-700">WhatsApp</label>
                <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $supplier->whatsapp) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="payment_terms" class="block text-sm font-medium text-slate-700">Payment terms</label>
                <input id="payment_terms" name="payment_terms" value="{{ old('payment_terms', $supplier->payment_terms) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="notes" class="block text-sm font-medium text-slate-700">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">{{ old('notes', $supplier->notes) }}</textarea>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active)) class="rounded border-slate-300">
                Active
            </label>

            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save changes
            </button>
        </form>
    </div>
@endsection

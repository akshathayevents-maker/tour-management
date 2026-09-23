@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
    <x-page-header :title="$supplier->name" subtitle="{{ $supplier->type?->label() ?? 'Supplier' }}">
        <x-slot:actions>
            <a href="{{ route('suppliers.edit', $supplier) }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Edit
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="max-w-2xl bg-white rounded-lg border border-slate-200 p-5">
        <div class="flex justify-between items-start mb-3">
            <h3 class="text-sm font-medium text-slate-900">Details</h3>
            <x-status-badge :color="$supplier->is_active ? 'green' : 'slate'" :label="$supplier->is_active ? 'Active' : 'Inactive'" />
        </div>
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-slate-500">Phone</dt><dd class="text-slate-900">{{ $supplier->phone ?? '—' }}</dd>
            <dt class="text-slate-500">WhatsApp</dt><dd class="text-slate-900">{{ $supplier->whatsapp ?? '—' }}</dd>
            <dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ $supplier->email ?? '—' }}</dd>
            <dt class="text-slate-500">Payment terms</dt><dd class="text-slate-900">{{ $supplier->payment_terms ?? '—' }}</dd>
        </dl>
        @if ($supplier->notes)
            <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $supplier->notes }}</p>
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
    <x-page-header :title="$supplier->name" subtitle="{{ $supplier->type?->label() ?? 'Supplier' }}">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('suppliers.edit', $supplier) }}">Edit</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5">
        <x-status-badge :color="$supplier->is_active ? 'green' : 'slate'" :label="$supplier->is_active ? 'Active' : 'Inactive'" />
    </div>

    <div class="max-w-2xl">
        <x-summary-panel title="Details">
            <dl class="divide-y divide-slate-50">
                <x-detail-row label="Phone">{{ $supplier->phone ?? '—' }}</x-detail-row>
                <x-detail-row label="WhatsApp">{{ $supplier->whatsapp ?? '—' }}</x-detail-row>
                <x-detail-row label="Email">{{ $supplier->email ?? '—' }}</x-detail-row>
                <x-detail-row label="Payment terms">{{ $supplier->payment_terms ?? '—' }}</x-detail-row>
            </dl>
            @if ($supplier->notes)
                <p class="mt-3 text-sm text-slate-600 border-t border-slate-100 pt-3">{{ $supplier->notes }}</p>
            @endif
        </x-summary-panel>
    </div>
@endsection

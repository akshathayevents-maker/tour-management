@extends('layouts.app')

@section('title', 'Add company')

@section('content')
    <x-page-header title="Add company" />

    <div class="max-w-2xl bg-white rounded-lg border border-slate-200 px-6">
        <form method="POST" action="{{ route('admin.companies.store') }}">
            @csrf
            <x-form-section title="Company details">
                <x-field label="Company name" name="name" required wide>
                    <input id="name" name="name" value="{{ old('name') }}" required autofocus
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Email" name="email" hint="Optional.">
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
            </x-form-section>

            <div class="flex items-center justify-end gap-2 py-4 border-t border-slate-100">
                <x-button tag="a" href="{{ route('admin.companies.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Create company</x-button>
            </div>
        </form>
    </div>
@endsection

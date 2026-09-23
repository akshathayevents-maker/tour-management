@extends('layouts.app')

@section('title', 'Add user')

@section('content')
    <x-page-header title="Add user" />

    <div class="max-w-2xl bg-white rounded-lg border border-slate-200 px-6">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <x-form-section title="Identity">
                <x-field label="Name" name="name" required>
                    <input id="name" name="name" value="{{ old('name') }}" required autofocus
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
                <x-field label="Email" name="email" required>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                           class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                </x-field>
            </x-form-section>

            @role('super_admin')
                <x-form-section title="Access" description="Which company and role this user belongs to.">
                    <x-field label="Company" name="company_id" required>
                        <select id="company_id" name="company_id" required
                                class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                            <option value="">Select a company</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field label="Role" name="role" required>
                        <select id="role" name="role" required class="block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                            <option value="company_admin" @selected(old('role') === 'company_admin')>Company admin</option>
                            <option value="company_user" @selected(old('role') === 'company_user')>Company user</option>
                        </select>
                    </x-field>
                </x-form-section>
            @else
                <input type="hidden" name="role" value="company_user">
            @endrole

            <p class="text-xs text-slate-400 py-4 border-t border-slate-100">
                The new user will receive an email to set their own password.
            </p>

            <div class="flex items-center justify-end gap-2 pb-4">
                <x-button tag="a" href="{{ route('admin.users.index') }}" variant="secondary">Cancel</x-button>
                <x-button type="submit">Send invite</x-button>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Add user')

@section('content')
    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>

            @role('super_admin')
                <div>
                    <label for="company_id" class="block text-sm font-medium text-slate-700">Company</label>
                    <select id="company_id" name="company_id" required
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        <option value="">Select a company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                    <select id="role" name="role" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        <option value="company_admin" @selected(old('role') === 'company_admin')>Company admin</option>
                        <option value="company_user" @selected(old('role') === 'company_user')>Company user</option>
                    </select>
                </div>
            @else
                <input type="hidden" name="role" value="company_user">
            @endrole

            <p class="text-xs text-slate-400">
                The new user will receive an email to set their own password.
            </p>

            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Send invite
            </button>
        </form>
    </div>
@endsection

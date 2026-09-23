@extends('layouts.app')

@section('title', 'Edit company')

@section('content')
    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Company name</label>
                <input id="name" name="name" value="{{ old('name', $company->name) }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $company->email) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Save changes
            </button>
        </form>
    </div>
@endsection

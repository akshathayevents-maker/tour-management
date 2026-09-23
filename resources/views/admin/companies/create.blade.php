@extends('layouts.app')

@section('title', 'Add company')

@section('content')
    <div class="max-w-lg bg-white rounded-lg border border-slate-200 p-6">
        <form method="POST" action="{{ route('admin.companies.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Company name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email <span class="text-slate-400">(optional)</span></label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
            </div>
            <button type="submit" class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Create company
            </button>
        </form>
    </div>
@endsection

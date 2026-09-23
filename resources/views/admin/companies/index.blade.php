@extends('layouts.app')

@section('title', 'Companies')

@section('content')
    <x-page-header title="Companies" subtitle="Every tenant on the platform.">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.companies.create') }}">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                Add company
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="hidden sm:block bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium text-slate-500">Name</th>
                    <th class="px-4 py-2.5 text-left font-medium text-slate-500">Email</th>
                    <th class="px-4 py-2.5 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($companies as $company)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5">
                            <a href="{{ route('admin.companies.edit', $company) }}" class="text-slate-900 font-medium hover:text-brand-700">
                                {{ $company->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2.5 text-slate-500">{{ $company->email ?? '—' }}</td>
                        <td class="px-4 py-2.5">
                            <x-status-badge :color="$company->is_active ? 'green' : 'slate'" :label="$company->is_active ? 'Active' : 'Inactive'" />
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <form method="POST" action="{{ route('admin.companies.toggle-active', $company) }}">
                                @csrf
                                @method('PATCH')
                                <button class="text-xs font-medium text-slate-500 hover:text-slate-900">
                                    {{ $company->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-400">No companies yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sm:hidden space-y-2">
        @forelse ($companies as $company)
            <div class="bg-white rounded-lg border border-slate-200 p-3.5">
                <div class="flex items-start justify-between gap-2">
                    <a href="{{ route('admin.companies.edit', $company) }}" class="text-sm font-medium text-slate-900 truncate">{{ $company->name }}</a>
                    <x-status-badge :color="$company->is_active ? 'green' : 'slate'" :label="$company->is_active ? 'Active' : 'Inactive'" />
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-xs text-slate-500">{{ $company->email ?? '—' }}</p>
                    <form method="POST" action="{{ route('admin.companies.toggle-active', $company) }}">
                        @csrf
                        @method('PATCH')
                        <button class="text-xs font-medium text-slate-500 hover:text-slate-900">
                            {{ $company->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-center text-slate-400 py-6">No companies yet.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $companies->links() }}</div>
@endsection

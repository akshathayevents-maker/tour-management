@extends('layouts.app')

@section('title', 'Companies')

@section('content')
    <div class="flex justify-between items-center mb-4">
        <p class="text-sm text-slate-500">Every tenant on the platform.</p>
        <a href="{{ route('admin.companies.create') }}"
           class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
            Add company
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Email</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($companies as $company)
                    <tr>
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.companies.edit', $company) }}" class="text-slate-900 hover:underline">
                                {{ $company->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $company->email ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $company->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $company->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('admin.companies.toggle-active', $company) }}">
                                @csrf
                                @method('PATCH')
                                <button class="text-sm text-slate-500 hover:text-slate-900">
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

    <div class="mt-4">{{ $companies->links() }}</div>
@endsection

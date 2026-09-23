@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <x-page-header title="Users" :subtitle="auth()->user()->hasRole('super_admin') ? 'Staff across every company.' : 'Staff in your company.'">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.users.create') }}">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                Add user
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="hidden sm:block bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium text-slate-500">Name</th>
                    <th class="px-4 py-2.5 text-left font-medium text-slate-500">Email</th>
                    <th class="px-4 py-2.5 text-left font-medium text-slate-500">Company</th>
                    <th class="px-4 py-2.5 text-left font-medium text-slate-500">Role</th>
                    <th class="px-4 py-2.5 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <x-avatar :name="$user->name" size="sm" />
                                <span class="text-slate-900 font-medium">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-slate-500">{{ $user->email }}</td>
                        <td class="px-4 py-2.5 text-slate-500">{{ $user->company->name ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-slate-500">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="px-4 py-2.5">
                            <x-status-badge :color="$user->is_active ? 'green' : 'slate'" :label="$user->is_active ? 'Active' : 'Inactive'" />
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @can('update', $user)
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-xs font-medium text-slate-500 hover:text-slate-900">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-400">No users yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sm:hidden space-y-2">
        @forelse ($users as $user)
            <div class="flex items-center gap-3 bg-white rounded-lg border border-slate-200 p-3.5">
                <x-avatar :name="$user->name" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-900 truncate">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500">{{ $user->email }} @if($user->company) · {{ $user->company->name }} @endif</p>
                </div>
                <x-status-badge :color="$user->is_active ? 'green' : 'slate'" :label="$user->is_active ? 'Active' : 'Inactive'" />
            </div>
        @empty
            <p class="text-center text-slate-400 py-6">No users yet.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection

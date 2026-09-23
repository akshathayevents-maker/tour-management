@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="flex justify-between items-center mb-4">
        <p class="text-sm text-slate-500">
            @role('super_admin') Staff across every company. @else Staff in your company. @endrole
        </p>
        <a href="{{ route('admin.users.create') }}"
           class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
            Add user
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Email</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Company</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Role</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-2 text-slate-900">{{ $user->name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $user->email }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $user->company->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            @can('update', $user)
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-sm text-slate-500 hover:text-slate-900">
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

    <div class="mt-4">{{ $users->links() }}</div>
@endsection

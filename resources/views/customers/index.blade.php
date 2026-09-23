@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <x-page-header title="Customers" subtitle="Everyone you've done business with.">
        <x-slot:actions>
            <a href="{{ route('customers.create') }}"
               class="rounded-md bg-slate-900 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Add customer
            </a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, phone or email…"
               class="w-full sm:w-80 rounded-md border-slate-300 shadow-sm sm:text-sm">
    </form>

    @if ($customers->isEmpty())
        <x-empty-state
            title="No customers yet."
            description="Add your first customer to start managing your holiday enquiries."
            action-label="Add customer"
            :action-url="route('customers.create')" />
    @else
        <div class="bg-white rounded-lg border border-slate-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Phone</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">City</th>
                        <th class="px-4 py-2 text-left font-medium text-slate-500">Added</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('customers.show', $customer) }}" class="text-slate-900 font-medium hover:underline">
                                    {{ $customer->name }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-slate-500">{{ $customer->phone }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ $customer->city ?? '—' }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ $customer->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $customers->links() }}</div>
    @endif
@endsection

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if (auth()->user()->isSuperAdmin())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
            <h2 class="text-base font-medium text-slate-900">Welcome, {{ auth()->user()->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Platform-wide metrics land here as more companies onboard.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg border border-slate-200 p-4">
                <p class="text-xs text-slate-500">New leads (7 days)</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $newLeadsCount }}</p>
            </div>
            <div class="bg-white rounded-lg border border-slate-200 p-4">
                <p class="text-xs text-slate-500">New enquiries (7 days)</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $newEnquiriesCount }}</p>
            </div>
            <div class="bg-white rounded-lg border {{ $overdueFollowUps->isNotEmpty() ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-white' }} p-4">
                <p class="text-xs {{ $overdueFollowUps->isNotEmpty() ? 'text-red-600' : 'text-slate-500' }}">Overdue follow-ups</p>
                <p class="text-2xl font-semibold {{ $overdueFollowUps->isNotEmpty() ? 'text-red-700' : 'text-slate-900' }}">{{ $overdueFollowUps->count() }}</p>
            </div>
            <div class="bg-white rounded-lg border border-slate-200 p-4">
                <p class="text-xs text-slate-500">Due today</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $todayFollowUps->count() }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-medium text-slate-900">Needs attention today</h3>
                    <a href="{{ route('follow-ups.index') }}" class="text-xs text-slate-500 hover:underline">View all</a>
                </div>
                @forelse ($overdueFollowUps->concat($todayFollowUps) as $followUp)
                    @include('follow_ups._item', ['followUp' => $followUp])
                @empty
                    <p class="text-sm text-slate-400">Nothing pressing today. 🎉</p>
                @endforelse
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-medium text-slate-900">Recent leads</h3>
                        <a href="{{ route('leads.index') }}" class="text-xs text-slate-500 hover:underline">View all</a>
                    </div>
                    @forelse ($recentLeads as $lead)
                        <a href="{{ route('leads.show', $lead) }}" class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                            <span class="text-slate-900">{{ $lead->name }} @if($lead->destination) &middot; {{ $lead->destination }} @endif</span>
                            <x-status-badge :color="$lead->status->badgeColor()" :label="$lead->status->label()" />
                        </a>
                    @empty
                        <p class="text-sm text-slate-400">No leads yet.</p>
                    @endforelse
                </div>

                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-medium text-slate-900">Recent enquiries</h3>
                        <a href="{{ route('enquiries.index') }}" class="text-xs text-slate-500 hover:underline">View all</a>
                    </div>
                    @forelse ($recentEnquiries as $enquiry)
                        <a href="{{ route('enquiries.show', $enquiry) }}" class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                            <span class="text-slate-900">{{ $enquiry->customer->name }} &middot; {{ $enquiry->destination }}</span>
                            <x-status-badge :color="$enquiry->status->badgeColor()" :label="$enquiry->status->label()" />
                        </a>
                    @empty
                        <p class="text-sm text-slate-400">No enquiries yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
@endsection

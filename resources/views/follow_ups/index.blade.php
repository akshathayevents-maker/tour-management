@extends('layouts.app')

@section('title', 'Follow-ups')

@section('content')
    <x-page-header title="Follow-ups" subtitle="What needs a call today." />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h3 class="text-sm font-medium text-red-600 mb-2">Overdue ({{ $overdue->count() }})</h3>
            @forelse ($overdue as $followUp)
                @include('follow_ups._item', ['followUp' => $followUp])
            @empty
                <p class="text-sm text-slate-400">Nothing overdue.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h3 class="text-sm font-medium text-slate-900 mb-2">Due today ({{ $dueToday->count() }})</h3>
            @forelse ($dueToday as $followUp)
                @include('follow_ups._item', ['followUp' => $followUp])
            @empty
                <p class="text-sm text-slate-400">Nothing due today.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h3 class="text-sm font-medium text-slate-500 mb-2">Upcoming ({{ $upcoming->count() }})</h3>
            @forelse ($upcoming as $followUp)
                @include('follow_ups._item', ['followUp' => $followUp])
            @empty
                <p class="text-sm text-slate-400">Nothing scheduled.</p>
            @endforelse
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
        <h2 class="text-base font-medium text-slate-900">Welcome, {{ auth()->user()->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            This is the dashboard shell. Leads, follow-ups, bookings and payments widgets land here
            in the next phase, once those modules exist.
        </p>
    </div>
@endsection

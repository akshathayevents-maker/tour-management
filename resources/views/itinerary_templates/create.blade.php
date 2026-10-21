@extends('layouts.app')

@section('title', 'New itinerary template')

@section('content')
    <x-page-header title="New itinerary template" subtitle="Reusable day-by-day plan for a destination." />

    @if ($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @include('itinerary_templates._form', ['action' => route('itinerary-templates.store'), 'method' => 'POST', 'template' => null])
@endsection

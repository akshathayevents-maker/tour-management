@extends('layouts.app')

@section('title', 'Edit '.$template->name)

@section('content')
    <x-page-header :title="'Edit '.$template->name" subtitle="{{ $template->destination }}" />

    @if ($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    @include('itinerary_templates._form', ['action' => route('itinerary-templates.update', $template), 'method' => 'PATCH', 'template' => $template])
@endsection

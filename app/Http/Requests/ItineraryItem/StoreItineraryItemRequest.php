<?php

namespace App\Http\Requests\ItineraryItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreItineraryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('itineraryDay')->itinerary->trip);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

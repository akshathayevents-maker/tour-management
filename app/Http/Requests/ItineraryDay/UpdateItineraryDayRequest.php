<?php

namespace App\Http\Requests\ItineraryDay;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItineraryDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('itineraryDay')->itinerary->trip);
    }

    public function rules(): array
    {
        return [
            'day_number' => ['required', 'integer', 'min:1', 'max:365'],
            'date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

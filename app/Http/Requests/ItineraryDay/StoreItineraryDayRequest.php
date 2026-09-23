<?php

namespace App\Http\Requests\ItineraryDay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItineraryDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('itinerary')->trip);
    }

    public function rules(): array
    {
        return [
            'day_number' => [
                'required', 'integer', 'min:1', 'max:365',
                // A double-submit (double-click, back-button resubmit) would
                // otherwise hit the DB's unique constraint directly and
                // surface as a raw 500 instead of a normal validation error.
                Rule::unique('itinerary_days', 'day_number')->where('itinerary_id', $this->route('itinerary')->id),
            ],
            'date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

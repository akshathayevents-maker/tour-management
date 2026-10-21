<?php

namespace App\Http\Requests\ItineraryTemplate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItineraryTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('itineraryTemplate'));
    }

    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],

            'days' => ['required', 'array', 'min:1'],
            'days.*.day_number' => ['required', 'integer', 'min:1', 'max:365', 'distinct'],
            'days.*.notes' => ['nullable', 'string', 'max:2000'],
            'days.*.items' => ['nullable', 'array'],
            'days.*.items.*.title' => ['required', 'string', 'max:255'],
            'days.*.items.*.time' => ['nullable', 'date_format:H:i'],
            'days.*.items.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

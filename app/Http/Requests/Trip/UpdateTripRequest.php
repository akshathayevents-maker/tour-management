<?php

namespace App\Http\Requests\Trip;

use App\Enums\TripStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('trip'));
    }

    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'travellers_count' => ['nullable', 'integer', 'min:1', 'max:200'],
            'status' => ['required', Rule::enum(TripStatus::class)],
        ];
    }
}

<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'source' => ['required', new Enum(LeadSource::class)],
            'status' => ['required', new Enum(LeadStatus::class)],
            'destination' => ['nullable', 'string', 'max:255'],
            'travel_month' => ['nullable', 'date'],
            'travellers_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

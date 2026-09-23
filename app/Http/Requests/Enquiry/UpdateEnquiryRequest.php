<?php

namespace App\Http\Requests\Enquiry;

use App\Enums\EnquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('enquiry'));
    }

    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:255'],
            'status' => ['required', new Enum(EnquiryStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'adults' => ['nullable', 'integer', 'min:0', 'max:50'],
            'children' => ['nullable', 'integer', 'min:0', 'max:50'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'hotel_category' => ['nullable', 'string', 'max:100'],
            'transport_required' => ['nullable', 'boolean'],
            'meal_plan' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

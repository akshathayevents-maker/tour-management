<?php

namespace App\Http\Requests\Enquiry;

use App\Models\Customer;
use App\Models\Enquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Enquiry::class);
    }

    public function rules(): array
    {
        return [
            // Scoped to the user's own company's customers — never trust a
            // raw customer_id into another tenant's record.
            'customer_id' => [
                'required',
                Rule::exists(Customer::class, 'id')->where('company_id', $this->user()->company_id),
            ],
            'destination' => ['required', 'string', 'max:255'],
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

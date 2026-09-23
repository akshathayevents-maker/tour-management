<?php

namespace App\Http\Requests\Trip;

use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Trip::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                Rule::exists(Customer::class, 'id')->where('company_id', $this->user()->company_id),
            ],
            'enquiry_id' => [
                'nullable',
                Rule::exists(Enquiry::class, 'id')->where('company_id', $this->user()->company_id),
            ],
            'destination' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'travellers_count' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}

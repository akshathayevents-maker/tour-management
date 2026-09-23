<?php

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Direct/manual booking creation — no quotation involved (repeat
 * customer, phone booking, WhatsApp booking).
 */
class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Booking::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                Rule::exists(Customer::class, 'id')->where('company_id', $this->user()->company_id),
            ],
            'trip_id' => [
                'required',
                Rule::exists(Trip::class, 'id')->where('company_id', $this->user()->company_id),
            ],
        ];
    }
}

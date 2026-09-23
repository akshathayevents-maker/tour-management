<?php

namespace App\Http\Requests\CustomerPayment;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('booking'));
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Booking $booking */
            $booking = $this->route('booking');
            $remaining = $booking->amountRemaining();

            if ($this->filled('amount') && (float) $this->input('amount') > $remaining) {
                $validator->errors()->add(
                    'amount',
                    'This payment would exceed the remaining balance of '.number_format($remaining, 2).'.'
                );
            }
        });
    }
}

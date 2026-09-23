<?php

namespace App\Http\Requests\SupplierPayment;

use Illuminate\Foundation\Http\FormRequest;

class VoidSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('supplierPayment')->bookingItem->booking);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}

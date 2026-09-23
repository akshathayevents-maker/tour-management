<?php

namespace App\Http\Requests\QuotationVersion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuotationVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('quotationVersion')->quotation);
    }

    public function rules(): array
    {
        return [
            'terms' => ['nullable', 'string', 'max:5000'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

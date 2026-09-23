<?php

namespace App\Http\Requests\QuotationLineItem;

use App\Enums\QuotationLineCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuotationLineItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('quotationLineItem')->quotationVersion->quotation);
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::enum(QuotationLineCategory::class)],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}

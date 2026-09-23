<?php

namespace App\Http\Requests\BookingItem;

use App\Enums\QuotationLineCategory;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bookingItem')->booking);
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::enum(QuotationLineCategory::class)],
            'supplier_id' => [
                'nullable',
                Rule::exists(Supplier::class, 'id')->where('company_id', $this->user()->company_id),
            ],
            'sell_price' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'confirmation_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

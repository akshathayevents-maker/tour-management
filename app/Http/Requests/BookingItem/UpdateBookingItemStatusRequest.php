<?php

namespace App\Http\Requests\BookingItem;

use App\Enums\BookingItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('bookingItem')->booking);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingItemStatus::class)],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}

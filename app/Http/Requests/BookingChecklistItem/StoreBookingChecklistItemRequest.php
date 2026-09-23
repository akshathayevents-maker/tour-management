<?php

namespace App\Http\Requests\BookingChecklistItem;

use App\Enums\ChecklistPhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('booking'));
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'phase' => ['nullable', Rule::enum(ChecklistPhase::class)],
        ];
    }
}

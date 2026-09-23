<?php

namespace App\Http\Requests\FollowUp;

use App\Models\FollowUp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    /**
     * Short, whitelisted aliases only — never accept a raw morph class
     * string from the client (that would let a request name any model).
     */
    public const SUBJECT_TYPES = [
        'lead' => \App\Models\Lead::class,
        'customer' => \App\Models\Customer::class,
        'enquiry' => \App\Models\Enquiry::class,
    ];

    public function authorize(): bool
    {
        return $this->user()->can('create', FollowUp::class);
    }

    public function rules(): array
    {
        return [
            'subject_type' => ['required', Rule::in(array_keys(self::SUBJECT_TYPES))],
            'subject_id' => ['required', 'integer'],
            'due_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function subjectClass(): string
    {
        return self::SUBJECT_TYPES[$this->string('subject_type')->value()];
    }
}

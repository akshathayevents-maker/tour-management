<?php

namespace App\Http\Requests\ItineraryBuilder;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateItineraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('trip'));
    }

    public function rules(): array
    {
        /** @var Trip $trip */
        $trip = $this->route('trip');

        return [
            // Replacing an existing itinerary's days is a deliberate,
            // confirmed action — never implicit from merely posting the
            // builder form twice. `accepted` is one of Laravel's implicit
            // rules (it validates even when the field is entirely absent
            // from the request), so it must only be attached when a
            // confirmation is actually required — stacking it
            // unconditionally with required_if would demand the checkbox
            // on every submission, including trips with no existing days.
            //
            // Deliberately NOT trusting the client-submitted
            // `has_existing_days` flag for this decision — it reflects the
            // state at page-load time, and a stale page (or a tampered
            // request) could submit `0` even though the trip's itinerary
            // now has days. Whether confirmation is required is derived
            // from the current database state on every submission.
            'confirm_replace' => $trip->itinerary()->whereHas('days')->exists() ? ['accepted'] : ['nullable'],

            'segments' => ['required', 'array', 'min:1'],
            'segments.*.destination' => ['required', 'string', 'max:255'],
            'segments.*.days' => ['required', 'integer', 'min:1', 'max:60'],
            'segments.*.notes' => ['nullable', 'string', 'max:2000'],
            'segments.*.itinerary_template_id' => [
                'nullable',
                'integer',
                // Deactivated templates are hidden from the builder's
                // dropdown, but that's only a UI convenience — a direct
                // request must be rejected server-side too, or
                // deactivation would mean nothing for a tampered request.
                Rule::exists('itinerary_templates', 'id')
                    ->where('company_id', $trip->company_id)
                    ->where('is_active', true),
            ],
        ];
    }
}

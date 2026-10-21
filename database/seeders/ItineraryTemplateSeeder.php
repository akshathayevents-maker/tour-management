<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ItineraryTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds a small, starter library of reusable itinerary templates for every
 * existing company.
 *
 * Templates are company-owned (itinerary_templates.company_id is a
 * required, NOT NULL foreign key — this schema has no concept of a
 * cross-company "shared" template), so this seeder runs once per company
 * rather than creating a single global set.
 *
 * Idempotent: a template is only created if no template with the same
 * (company_id, destination, name) already exists. If a company has
 * already created or edited a template under one of these names, this
 * seeder leaves it completely alone — it never updates or recreates an
 * existing template's days/items, so a rerun can never clobber a user's
 * customization.
 *
 * Activities here are deliberately generic placeholders — edit the
 * $destinations array below (or manage templates directly in the UI
 * at /itinerary-templates) rather than treating these as authoritative
 * claims about opening hours, prices, or availability.
 */
class ItineraryTemplateSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {
            foreach ($this->destinations() as $destination => $variants) {
                foreach ($variants as $variant) {
                    $this->seedTemplate($company, $destination, $variant);
                }
            }
        });
    }

    private function seedTemplate(Company $company, string $destination, array $variant): void
    {
        $exists = ItineraryTemplate::query()
            ->where('company_id', $company->id)
            ->where('destination', $destination)
            ->where('name', $variant['name'])
            ->exists();

        if ($exists) {
            return;
        }

        // forceCreate(), not create(): company_id is intentionally absent
        // from every tenant model's $fillable (see Concerns/BelongsToCompany)
        // and is normally auto-filled from the authenticated user — a
        // seeder has no authenticated actor, so it sets company_id directly.
        $template = ItineraryTemplate::forceCreate([
            'company_id' => $company->id,
            'destination' => $destination,
            'name' => $variant['name'],
            'description' => $variant['description'],
            'is_active' => true,
        ]);

        foreach ($variant['days'] as $dayNumber => $day) {
            $templateDay = $template->days()->forceCreate([
                'company_id' => $company->id,
                'day_number' => $dayNumber + 1,
                'notes' => $day['notes'] ?? null,
            ]);

            foreach ($day['items'] as $sortOrder => $title) {
                $templateDay->items()->forceCreate([
                    'company_id' => $company->id,
                    'title' => $title,
                    'sort_order' => $sortOrder,
                ]);
            }
        }
    }

    /**
     * Starter data — edit freely. Each destination can have multiple
     * duration variants. Activities are intentionally generic; adjust
     * per destination once real, verified details are available.
     *
     * @return array<string, array<int, array{name: string, description: string, days: array<int, array{notes?: string, items: array<int, string>}>}>>
     */
    private function destinations(): array
    {
        $arrival = ['Arrive and check in to the hotel', 'Freshen up and relax', 'Evening at leisure'];
        $departure = ['Breakfast at the hotel', 'Check out', 'Depart for the next destination / return journey'];
        $freeDay = ['Breakfast at the hotel', 'Free time / leisure exploring on your own', 'Evening at leisure'];

        return [
            'Ooty' => [
                [
                    'name' => 'Quick 2-day escape',
                    'description' => 'A short, relaxed trip covering the essentials. Edit activities to match your usual itinerary.',
                    'days' => [
                        ['notes' => 'Arrival day', 'items' => $arrival],
                        ['notes' => 'Sightseeing and departure', 'items' => ['Breakfast at the hotel', 'Local sightseeing (botanical garden, lake, viewpoint)', 'Check out and depart']],
                    ],
                ],
                [
                    'name' => 'Relaxed 4-day retreat',
                    'description' => 'A slower-paced plan with a free day built in for leisure or local exploring.',
                    'days' => [
                        ['notes' => 'Arrival day', 'items' => $arrival],
                        ['notes' => 'Sightseeing', 'items' => ['Breakfast at the hotel', 'Local sightseeing (botanical garden, lake)', 'Local market visit in the evening']],
                        ['notes' => 'Free day', 'items' => $freeDay],
                        ['notes' => 'Departure', 'items' => $departure],
                    ],
                ],
            ],

            'Munnar' => [
                [
                    'name' => 'Weekend 2-day trip',
                    'description' => 'A compact weekend plan. Adjust activity order and timings to suit the group.',
                    'days' => [
                        ['notes' => 'Arrival day', 'items' => $arrival],
                        ['notes' => 'Sightseeing and departure', 'items' => ['Breakfast at the hotel', 'Scenic viewpoint visit', 'Tea estate / plantation visit', 'Check out and depart']],
                    ],
                ],
                [
                    'name' => 'Extended 5-day plan',
                    'description' => 'A longer stay with room for leisure days — fill in or remove activities as needed.',
                    'days' => [
                        ['notes' => 'Arrival day', 'items' => $arrival],
                        ['notes' => 'Sightseeing', 'items' => ['Breakfast at the hotel', 'Tea estate / plantation visit', 'Local market visit']],
                        ['notes' => 'Sightseeing', 'items' => ['Breakfast at the hotel', 'Scenic viewpoint visit', 'Local cuisine tasting']],
                        ['notes' => 'Free day', 'items' => $freeDay],
                        ['notes' => 'Departure', 'items' => $departure],
                    ],
                ],
            ],

            'Goa' => [
                [
                    'name' => 'Short 3-day beach break',
                    'description' => 'A simple beach-focused starter plan. Replace with specific beach/activity names as needed.',
                    'days' => [
                        ['notes' => 'Arrival day', 'items' => $arrival],
                        ['notes' => 'Beach day', 'items' => ['Breakfast at the hotel', 'Beach visit / water activities', 'Local cuisine tasting in the evening']],
                        ['notes' => 'Departure', 'items' => $departure],
                    ],
                ],
            ],
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ItineraryTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function companyUser(Company $company, string $role = 'company_admin'): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_authorized_user_can_create_a_template_with_days_and_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $response = $this->actingAs($user)->post(route('itinerary-templates.store'), [
            'destination' => 'Ooty',
            'name' => 'Relaxed 2-day',
            'description' => 'A gentle pace through the hills.',
            'is_active' => 1,
            'days' => [
                [
                    'day_number' => 1,
                    'notes' => 'Arrival',
                    'items' => [
                        ['title' => 'Check in to hotel', 'time' => '14:00', 'notes' => null],
                        ['title' => 'Botanical garden walk', 'time' => '16:00', 'notes' => null],
                    ],
                ],
                [
                    'day_number' => 2,
                    'items' => [
                        ['title' => 'Lake boating', 'time' => '09:00', 'notes' => null],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('itinerary-templates.index'));

        $template = ItineraryTemplate::where('name', 'Relaxed 2-day')->first();
        $this->assertNotNull($template);
        $this->assertSame($company->id, $template->company_id);
        $this->assertCount(2, $template->days);
        $this->assertCount(2, $template->days->firstWhere('day_number', 1)->items);
        $this->assertCount(1, $template->days->firstWhere('day_number', 2)->items);
    }

    public function test_invalid_template_data_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        // Missing destination/name, zero days.
        $response = $this->actingAs($user)->post(route('itinerary-templates.store'), [
            'days' => [],
        ]);

        $response->assertSessionHasErrors(['destination', 'name', 'days']);
        $this->assertSame(0, ItineraryTemplate::count());
    }

    public function test_invalid_day_counts_are_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $response = $this->actingAs($user)->post(route('itinerary-templates.store'), [
            'destination' => 'Ooty',
            'name' => 'Broken template',
            'days' => [
                ['day_number' => 0], // below min:1
                ['day_number' => 0], // duplicate + invalid
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(0, ItineraryTemplate::count());
    }

    public function test_updating_a_template_does_not_mutate_previously_generated_itineraries(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $this->actingAs($user);

        $template = ItineraryTemplate::factory()->create(['company_id' => $company->id, 'name' => 'Original']);
        $day = $template->days()->create(['company_id' => $company->id, 'day_number' => 1]);
        $day->items()->create(['company_id' => $company->id, 'title' => 'Original activity', 'sort_order' => 0]);

        $trip = \App\Models\Trip::factory()->create([
            'company_id' => $company->id,
            'customer_id' => \App\Models\Customer::factory()->create(['company_id' => $company->id])->id,
        ]);

        app(\App\Services\ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
        ]);

        $generatedItem = $trip->fresh()->itinerary->days->first()->items->first();
        $this->assertSame('Original activity', $generatedItem->title);

        // Now update the template's content entirely.
        $this->actingAs($user)->patch(route('itinerary-templates.update', $template), [
            'destination' => 'Ooty',
            'name' => 'Rewritten',
            'days' => [
                ['day_number' => 1, 'items' => [['title' => 'Completely different activity']]],
            ],
        ]);

        // The already-generated trip item must be untouched.
        $this->assertSame('Original activity', $generatedItem->fresh()->title);
    }

    public function test_unauthorized_user_cannot_manage_templates(): void
    {
        $response = $this->post(route('itinerary-templates.store'), [
            'destination' => 'Ooty',
            'name' => 'Nope',
            'days' => [['day_number' => 1]],
        ]);

        $response->assertRedirect(route('login'));
        $this->assertSame(0, ItineraryTemplate::count());
    }

    public function test_cross_company_template_access_is_rejected(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $templateB = ItineraryTemplate::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->get(route('itinerary-templates.edit', $templateB))->assertNotFound();

        $this->actingAs($userA)->patch(route('itinerary-templates.update', $templateB), [
            'destination' => 'Hacked',
            'name' => 'Hacked',
            'days' => [['day_number' => 1]],
        ])->assertNotFound();

        $this->assertNotSame('Hacked', $templateB->fresh()->name);
    }

    public function test_deleting_a_template_deactivates_it_without_affecting_generated_itineraries(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $this->actingAs($user);

        $template = ItineraryTemplate::factory()->create(['company_id' => $company->id]);
        $day = $template->days()->create(['company_id' => $company->id, 'day_number' => 1]);
        $day->items()->create(['company_id' => $company->id, 'title' => 'Kept activity', 'sort_order' => 0]);

        $trip = \App\Models\Trip::factory()->create([
            'company_id' => $company->id,
            'customer_id' => \App\Models\Customer::factory()->create(['company_id' => $company->id])->id,
        ]);

        app(\App\Services\ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
        ]);

        $this->actingAs($user)->delete(route('itinerary-templates.destroy', $template))
            ->assertRedirect(route('itinerary-templates.index'));

        $this->assertSame('Kept activity', $trip->fresh()->itinerary->days->first()->items->first()->title);
    }
}

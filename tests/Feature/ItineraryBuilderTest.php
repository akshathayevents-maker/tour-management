<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Itinerary;
use App\Models\ItineraryTemplate;
use App\Models\Trip;
use App\Models\User;
use App\Services\ItineraryGenerationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Creates the user AND establishes it as the authenticated actor —
     * BelongsToCompany only auto-fills company_id when auth()->check() is
     * true (see Concerns/BelongsToCompany), exactly like every real
     * request in this app. Direct model/service calls in these tests
     * mirror that by running inside an acting-as context too.
     */
    private function companyUser(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_admin');
        $this->actingAs($user);

        return $user;
    }

    private function templateWithDays(Company $company, string $destination, int $days, int $itemsPerDay = 1): ItineraryTemplate
    {
        $template = ItineraryTemplate::factory()->create(['company_id' => $company->id, 'destination' => $destination]);

        for ($d = 1; $d <= $days; $d++) {
            $day = $template->days()->create(['company_id' => $company->id, 'day_number' => $d]);

            for ($i = 0; $i < $itemsPerDay; $i++) {
                $day->items()->create([
                    'company_id' => $company->id,
                    'title' => "{$destination} day {$d} activity {$i}",
                    'sort_order' => $i,
                ]);
            }
        }

        return $template;
    }

    public function test_multiple_destinations_generate_correct_sequential_days(): void
    {
        $company = Company::factory()->create();
        $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        $ooty = $this->templateWithDays($company, 'Ooty', 2);
        $coonoor = $this->templateWithDays($company, 'Coonoor', 1);
        $mysuru = $this->templateWithDays($company, 'Mysuru', 3);

        app(ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 2, 'itinerary_template_id' => $ooty->id],
            ['destination' => 'Coonoor', 'days' => 1, 'itinerary_template_id' => $coonoor->id],
            ['destination' => 'Mysuru', 'days' => 3, 'itinerary_template_id' => $mysuru->id],
        ]);

        $days = $trip->fresh()->itinerary->days;

        $this->assertCount(6, $days);
        $this->assertSame([1, 2, 3, 4, 5, 6], $days->pluck('day_number')->all());
        $this->assertSame(['Ooty', 'Ooty', 'Coonoor', 'Mysuru', 'Mysuru', 'Mysuru'], $days->pluck('destination')->all());
    }

    public function test_activities_are_copied_into_trip_specific_records(): void
    {
        $company = Company::factory()->create();
        $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 1, 2);

        app(ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
        ]);

        $generatedDay = $trip->fresh()->itinerary->days->first();
        $this->assertCount(2, $generatedDay->items);

        // Genuinely separate records in a different table, not references
        // to the template's own items — compare model class, not raw ids
        // (which can coincidentally collide across two independent tables).
        $this->assertInstanceOf(\App\Models\ItineraryItem::class, $generatedDay->items->first());
        $this->assertSame(
            $template->days->first()->items->pluck('title')->all(),
            $generatedDay->items->pluck('title')->all()
        );
    }

    public function test_generated_activities_can_be_edited_without_changing_the_source_template(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 1, 1);

        app(ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
        ]);

        $generatedItem = $trip->fresh()->itinerary->days->first()->items->first();

        $this->actingAs($user)->patch(route('itinerary-items.update', $generatedItem), [
            'title' => 'Edited by the trip owner',
        ]);

        $this->assertSame('Edited by the trip owner', $generatedItem->fresh()->title);
        $this->assertSame('Ooty day 1 activity 0', $template->days->first()->items->first()->fresh()->title);
    }

    public function test_a_template_with_more_days_than_requested_only_uses_the_requested_days(): void
    {
        $company = Company::factory()->create();
        $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 3); // 3-day template

        app(ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id], // only 1 requested
        ]);

        $days = $trip->fresh()->itinerary->days;
        $this->assertCount(1, $days);
        $this->assertStringContainsString('day 1', $days->first()->items->first()->title);
    }

    public function test_a_template_with_fewer_days_than_requested_leaves_extra_days_blank(): void
    {
        $company = Company::factory()->create();
        $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 1); // 1-day template

        app(ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 3, 'itinerary_template_id' => $template->id], // 3 requested
        ]);

        $days = $trip->fresh()->itinerary->days;
        $this->assertCount(3, $days);
        $this->assertCount(1, $days->get(0)->items); // from template
        $this->assertCount(0, $days->get(1)->items); // blank, not invented
        $this->assertCount(0, $days->get(2)->items); // blank, not invented
    }

    public function test_regenerating_replaces_rather_than_duplicates_days(): void
    {
        $company = Company::factory()->create();
        $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 2);

        $service = app(ItineraryGenerationService::class);

        $service->generate($trip, [
            ['destination' => 'Ooty', 'days' => 2, 'itinerary_template_id' => $template->id],
        ]);
        $service->generate($trip, [
            ['destination' => 'Ooty', 'days' => 2, 'itinerary_template_id' => $template->id],
        ]);

        // Still exactly one itinerary, still exactly 2 days — not 4.
        $this->assertSame(1, Itinerary::where('trip_id', $trip->id)->count());
        $this->assertCount(2, $trip->fresh()->itinerary->days);
    }

    public function test_failed_generation_rolls_back_partial_writes(): void
    {
        $company = Company::factory()->create();
        $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        // company_id is intentionally never mass-assignable (see
        // BelongsToCompany) — it's always auto-filled from the acting
        // user, and that auto-fill is deliberately skipped for a
        // super_admin (who has no company of their own). Acting as one
        // here forces a genuine, cross-driver-enforced NOT NULL violation
        // on itineraries.company_id partway through generation, proving
        // the whole operation rolls back rather than leaving a
        // half-built itinerary behind.
        $superAdmin = User::factory()->create(['company_id' => null]);
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);

        try {
            app(ItineraryGenerationService::class)->generate($trip, [
                ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => null],
            ]);
            $this->fail('Expected generation to throw.');
        } catch (\Throwable) {
            // expected
        }

        $this->assertNull($trip->fresh()->itinerary);
    }

    public function test_existing_itineraries_remain_accessible_and_editable_without_templates(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        // Manually created itinerary, the pre-existing workflow — no templates involved.
        $itinerary = $trip->itinerary()->create(['company_id' => $company->id]);
        $day = $itinerary->days()->create(['company_id' => $company->id, 'day_number' => 1]);

        $response = $this->actingAs($user)->patch(route('itinerary-days.update', $day), [
            'day_number' => 1,
            'notes' => 'Still editable the old way',
        ]);

        $response->assertRedirect(route('trips.show', $trip));
        $this->assertSame('Still editable the old way', $day->fresh()->notes);
    }

    public function test_builder_requires_confirmation_before_replacing_an_existing_itinerary(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 1);

        $itinerary = $trip->itinerary()->create(['company_id' => $company->id]);
        $itinerary->days()->create(['company_id' => $company->id, 'day_number' => 1]);

        $response = $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
            'has_existing_days' => 1,
            // confirm_replace intentionally omitted
            'segments' => [
                ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
            ],
        ]);

        $response->assertSessionHasErrors('confirm_replace');
        $this->assertCount(1, $trip->fresh()->itinerary->days);
    }

    public function test_cross_company_template_id_is_rejected_by_the_generation_request(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $this->companyUser($companyB); // auth context to create companyB's own template
        $foreignTemplate = $this->templateWithDays($companyB, 'Ooty', 1);

        $user = $this->companyUser($companyA); // switch to the actual actor
        $trip = Trip::factory()->create(['company_id' => $companyA->id]);

        $response = $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
            'segments' => [
                ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $foreignTemplate->id],
            ],
        ]);

        $response->assertSessionHasErrors('segments.0.itinerary_template_id');
        $this->assertNull($trip->fresh()->itinerary);
    }

    public function test_cannot_build_itinerary_for_another_companys_trip(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $foreignTrip = Trip::factory()->create(['company_id' => $companyB->id]);

        $user = $this->companyUser($companyA);

        $this->actingAs($user)->get(route('trips.itinerary-builder.create', $foreignTrip))->assertNotFound();

        $this->actingAs($user)->post(route('trips.itinerary-builder.store', $foreignTrip), [
            'segments' => [['destination' => 'Ooty', 'days' => 1]],
        ])->assertNotFound();
    }

    public function test_full_workflow_through_the_real_controller_generates_and_redirects(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Mysuru', 2);

        $response = $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
            'segments' => [
                ['destination' => 'Mysuru', 'days' => 2, 'itinerary_template_id' => $template->id],
            ],
        ]);

        $response->assertRedirect(route('trips.show', $trip));
        $this->assertCount(2, $trip->fresh()->itinerary->days);
    }

    public function test_deactivated_template_cannot_be_selected_via_direct_request_but_builder_still_lists_it_as_hidden(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = ItineraryTemplate::factory()->create([
            'company_id' => $company->id,
            'destination' => 'Ooty',
            'name' => 'Deactivated Ooty Plan',
        ]);
        $template->days()->create(['company_id' => $company->id, 'day_number' => 1]);
        $template->update(['is_active' => false]);

        // Hidden from the builder's own dropdown (placeholder text also
        // happens to say "e.g. Ooty", so assert on the template's own
        // distinctive name rather than the destination string).
        $builderBody = $this->actingAs($user)->get(route('trips.itinerary-builder.create', $trip))->getContent();
        $this->assertStringNotContainsString('Deactivated Ooty Plan', $builderBody);

        // And rejected server-side even if submitted directly (tampered request).
        $response = $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
            'segments' => [
                ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
            ],
        ]);

        $response->assertSessionHasErrors('segments.0.itinerary_template_id');
        $this->assertNull($trip->fresh()->itinerary);
    }

    public function test_deactivating_a_template_does_not_affect_an_already_generated_itinerary(): void
    {
        $company = Company::factory()->create();
        $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 1);

        app(ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
        ]);

        $template->update(['is_active' => false]);

        $this->assertCount(1, $trip->fresh()->itinerary->days);
        $this->assertCount(1, $trip->fresh()->itinerary->days->first()->items);
    }

    public function test_deleting_a_template_day_safely_nulls_provenance_on_the_generated_day(): void
    {
        $company = Company::factory()->create();
        $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 1);

        app(ItineraryGenerationService::class)->generate($trip, [
            ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
        ]);

        $generatedDay = $trip->fresh()->itinerary->days->first();
        $this->assertNotNull($generatedDay->source_itinerary_template_day_id);

        // Delete the source template day outright.
        $template->days->first()->delete();

        // The generated day survives, untouched apart from the now-null
        // traceability link (nullOnDelete, not cascadeOnDelete).
        $generatedDay->refresh();
        $this->assertNull($generatedDay->source_itinerary_template_day_id);
        $this->assertCount(1, $generatedDay->items);
    }

    public function test_segment_day_count_rejects_zero_negative_and_excessive_values(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        foreach ([0, -1, 61] as $invalidDays) {
            $response = $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
                'segments' => [
                    ['destination' => 'Ooty', 'days' => $invalidDays],
                ],
            ]);

            $response->assertSessionHasErrors('segments.0.days');
        }

        $this->assertNull($trip->fresh()->itinerary);
    }

    public function test_reordering_destinations_preserves_the_submitted_sequence(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        // Submitted in Mysuru, Ooty order — the reverse of alphabetical —
        // proving day numbering follows submission order, not any
        // incidental database/alphabetical ordering.
        $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
            'segments' => [
                ['destination' => 'Mysuru', 'days' => 1],
                ['destination' => 'Ooty', 'days' => 1],
            ],
        ]);

        $days = $trip->fresh()->itinerary->days;
        $this->assertSame(['Mysuru', 'Ooty'], $days->pluck('destination')->all());
        $this->assertSame([1, 2], $days->pluck('day_number')->all());
    }

    public function test_tampered_has_existing_days_flag_cannot_bypass_the_replace_confirmation(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        $itinerary = $trip->itinerary()->create(['company_id' => $company->id]);
        $itinerary->days()->create(['company_id' => $company->id, 'day_number' => 1]);

        // Submits has_existing_days=0 even though the trip genuinely has
        // an existing day — simulating a stale page or a tampered
        // request. The server must derive the real state itself rather
        // than trusting this client-submitted flag.
        $response = $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
            'has_existing_days' => 0,
            'segments' => [
                ['destination' => 'Ooty', 'days' => 1],
            ],
        ]);

        $response->assertSessionHasErrors('confirm_replace');
        $this->assertCount(1, $trip->fresh()->itinerary->days);
    }

    public function test_failed_regeneration_preserves_the_previously_saved_itinerary(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $template = $this->templateWithDays($company, 'Ooty', 1);

        $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
            'segments' => [
                ['destination' => 'Ooty', 'days' => 1, 'itinerary_template_id' => $template->id],
            ],
        ]);
        $this->assertCount(1, $trip->fresh()->itinerary->days);

        // Attempt a regeneration that fails validation (missing the
        // required confirmation) — the existing saved itinerary must be
        // completely unaffected.
        $response = $this->actingAs($user)->post(route('trips.itinerary-builder.store', $trip), [
            'segments' => [
                ['destination' => 'Mysuru', 'days' => 3, 'itinerary_template_id' => $template->id],
            ],
        ]);

        $response->assertSessionHasErrors('confirm_replace');

        $preservedDays = $trip->fresh()->itinerary->days;
        $this->assertCount(1, $preservedDays);
        $this->assertSame('Ooty', $preservedDays->first()->destination);
        $this->assertSame('Ooty day 1 activity 0', $preservedDays->first()->items->first()->title);
    }

    public function test_template_management_endpoints_require_authentication(): void
    {
        $company = Company::factory()->create();
        $template = ItineraryTemplate::factory()->create(['company_id' => $company->id]);

        $this->get(route('itinerary-templates.index'))->assertRedirect(route('login'));
        $this->get(route('itinerary-templates.create'))->assertRedirect(route('login'));
        $this->get(route('itinerary-templates.edit', $template))->assertRedirect(route('login'));
        $this->patch(route('itinerary-templates.toggle-active', $template))->assertRedirect(route('login'));
        $this->delete(route('itinerary-templates.destroy', $template))->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ItineraryTemplate;
use Database\Seeders\ItineraryTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryTemplateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_starter_templates_for_every_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $this->seed(ItineraryTemplateSeeder::class);

        $this->assertGreaterThan(0, ItineraryTemplate::where('company_id', $companyA->id)->count());
        $this->assertGreaterThan(0, ItineraryTemplate::where('company_id', $companyB->id)->count());

        // Each template has at least one day, and every day's activities
        // are non-empty — the starter content is actually usable.
        ItineraryTemplate::with('days.items')->get()->each(function (ItineraryTemplate $template) {
            $this->assertNotEmpty($template->days, "{$template->name} has no days.");
            $template->days->each(fn ($day) => $this->assertNotEmpty($day->items, "{$template->name} day {$day->day_number} has no activities."));
        });
    }

    public function test_seeding_twice_does_not_create_duplicates(): void
    {
        Company::factory()->create();

        $this->seed(ItineraryTemplateSeeder::class);
        $countAfterFirstRun = ItineraryTemplate::count();

        $this->seed(ItineraryTemplateSeeder::class);
        $countAfterSecondRun = ItineraryTemplate::count();

        $this->assertSame($countAfterFirstRun, $countAfterSecondRun);
        $this->assertGreaterThan(0, $countAfterFirstRun);
    }

    public function test_seeding_never_overwrites_a_customized_template(): void
    {
        $company = Company::factory()->create();

        $this->seed(ItineraryTemplateSeeder::class);

        $template = ItineraryTemplate::where('company_id', $company->id)->first();
        $originalName = $template->name;
        $template->update(['description' => 'Customized by the company — do not overwrite.']);

        // Simulate the template being renamed back to clash with a starter
        // entry's (destination, name) pair isn't the point here — the
        // point is that re-running the seeder leaves every existing
        // template completely alone, customized or not.
        $this->seed(ItineraryTemplateSeeder::class);

        $this->assertSame('Customized by the company — do not overwrite.', $template->fresh()->description);
        $this->assertSame($originalName, $template->fresh()->name);
    }

    public function test_seeded_templates_are_scoped_to_their_own_company_only(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $this->seed(ItineraryTemplateSeeder::class);

        $templateIdsA = ItineraryTemplate::where('company_id', $companyA->id)->pluck('id');
        $templateIdsB = ItineraryTemplate::where('company_id', $companyB->id)->pluck('id');

        $this->assertEmpty($templateIdsA->intersect($templateIdsB));
    }
}

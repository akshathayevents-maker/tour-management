<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LeadActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function companyUser(Company $company, string $name = 'Test Agent'): User
    {
        $user = User::factory()->create(['company_id' => $company->id, 'name' => $name]);
        $user->assignRole('company_user');

        return $user;
    }

    private function leadFor(Company $company, array $overrides = []): Lead
    {
        return Lead::factory()->create(array_merge([
            'company_id' => $company->id,
            'status' => LeadStatus::New,
        ], $overrides));
    }

    private function baseUpdatePayload(Lead $lead, array $overrides = []): array
    {
        return array_merge([
            'name' => $lead->name,
            'phone' => $lead->phone,
            'source' => $lead->source->value,
            'status' => $lead->status->value,
        ], $overrides);
    }

    public function test_lead_show_page_loads_normally(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company);

        $this->actingAs($user)->get(route('leads.show', $lead))->assertOk();
    }

    public function test_lost_transition_creates_activity_and_it_appears_on_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company, 'Priya Sharma');
        $lead = $this->leadFor($company, ['status' => LeadStatus::Contacted]);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Lost->value,
            'lost_reason' => 'Booked with a competitor.',
        ]));

        $activity = Activity::forSubject($lead->fresh())->first();
        $this->assertNotNull($activity);
        $this->assertStringContainsString('Booked with a competitor.', $activity->description);

        $response = $this->actingAs($user)->get(route('leads.show', $lead));
        $response->assertOk()
            ->assertSee('Lead marked Lost: Booked with a competitor.')
            ->assertSee('Priya Sharma');
    }

    public function test_activity_timestamp_is_displayed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Contacted]);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Lost->value,
            'lost_reason' => 'Too expensive.',
        ]));

        $activity = Activity::forSubject($lead->fresh())->first();

        $this->actingAs($user)->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee($activity->created_at->diffForHumans());
    }

    public function test_multiple_activities_are_ordered_newest_first(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Contacted]);

        $first = activity()->performedOn($lead)->causedBy($user)->log('Lead marked Lost: First reason.');
        $first->update(['created_at' => now()->subMinute()]);
        activity()->performedOn($lead)->causedBy($user)->log('Lead marked Lost: Second reason.');

        $response = $this->actingAs($user)->get(route('leads.show', $lead));
        $response->assertOk();

        $content = $response->getContent();
        $firstPos = strpos($content, 'First reason.');
        $secondPos = strpos($content, 'Second reason.');

        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertLessThan($firstPos, $secondPos);
    }

    public function test_no_activities_produces_clean_empty_state(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::New]);

        $response = $this->actingAs($user)->get(route('leads.show', $lead));

        $response->assertOk()
            ->assertDontSee('Activity', false)
            ->assertDontSee('undefined')
            ->assertDontSee('null');
    }

    public function test_company_a_cannot_access_company_b_lead_or_its_activity(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $leadB = $this->leadFor($companyB, ['status' => LeadStatus::Lost, 'lost_reason' => 'Secret reason']);
        activity()->performedOn($leadB)->log('Lead marked Lost: Secret reason');

        $this->actingAs($userA)->get(route('leads.show', $leadB))->assertNotFound();
    }

    public function test_lost_reason_behavior_remains_intact(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Contacted]);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Lost->value,
        ]))->assertSessionHasErrors('lost_reason');
    }

    public function test_re_editing_an_already_lost_lead_does_not_create_duplicate_activity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Lost, 'lost_reason' => 'Original reason.']);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Lost->value,
            'lost_reason' => 'Updated reason text.',
        ]));

        $this->assertSame(0, Activity::forSubject($lead->fresh())->count());
    }

    public function test_lead_conversion_behavior_unchanged(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::New]);

        $this->actingAs($user)->post(route('leads.convert', $lead))->assertRedirect();

        $this->assertTrue($lead->fresh()->isConverted());
    }
}

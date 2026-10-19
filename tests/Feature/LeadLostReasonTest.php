<?php

namespace Tests\Feature;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LeadLostReasonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function companyUser(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
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

    public function test_lost_reason_not_required_during_creation(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post(route('leads.store'), [
            'name' => 'New Lead',
            'phone' => '9000000000',
            'source' => LeadSource::cases()[0]->value,
        ])->assertSessionDoesntHaveErrors();

        $this->assertNull(Lead::first()->lost_reason);
    }

    public function test_lost_reason_not_required_when_editing_non_lost_lead(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Contacted]);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Qualified->value,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertNull($lead->fresh()->lost_reason);
    }

    public function test_lost_reason_required_when_marking_lead_as_lost(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Contacted]);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Lost->value,
        ]))->assertSessionHasErrors('lost_reason');

        $this->assertSame(LeadStatus::Contacted, $lead->fresh()->status);
    }

    public function test_lead_marked_lost_with_reason_succeeds(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Contacted]);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Lost->value,
            'lost_reason' => 'Booked with a competitor.',
        ]))->assertSessionDoesntHaveErrors();

        $fresh = $lead->fresh();
        $this->assertSame(LeadStatus::Lost, $fresh->status);
        $this->assertSame('Booked with a competitor.', $fresh->lost_reason);
    }

    public function test_lost_reason_preserved_when_lead_moved_away_from_lost(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Lost, 'lost_reason' => 'Too expensive.']);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Contacted->value,
            'lost_reason' => 'Too expensive.',
        ]))->assertSessionDoesntHaveErrors();

        $this->assertSame('Too expensive.', $lead->fresh()->lost_reason);
        $this->assertSame(LeadStatus::Contacted, $lead->fresh()->status);
    }

    public function test_lead_lost_reason_appears_on_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Lost, 'lost_reason' => 'Chose a different destination.']);

        $this->actingAs($user)->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('Chose a different destination.');
    }

    public function test_activity_logged_when_lead_marked_lost(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::Contacted]);

        $this->actingAs($user)->put(route('leads.update', $lead), $this->baseUpdatePayload($lead, [
            'status' => LeadStatus::Lost->value,
            'lost_reason' => 'Price too high.',
        ]));

        $activity = Activity::forSubject($lead->fresh())->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('Lost', $activity->description);
        $this->assertStringContainsString('Price too high.', $activity->description);
    }

    public function test_no_activity_logged_when_lead_stays_lost(): void
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

    public function test_existing_non_lost_leads_continue_to_work_with_null_lost_reason(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::New]);

        $this->assertNull($lead->lost_reason);

        $this->actingAs($user)->get(route('leads.show', $lead))->assertOk();
    }

    public function test_lead_conversion_unaffected_by_lost_reason_field(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = $this->leadFor($company, ['status' => LeadStatus::New, 'destination' => 'Goa']);

        $this->actingAs($user)->post(route('leads.convert', $lead))->assertRedirect();

        $this->assertTrue($lead->fresh()->isConverted());
    }
}

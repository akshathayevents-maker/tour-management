<?php

namespace Tests\Feature;

use App\Enums\LeadSource;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
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

    public function test_minimal_lead_can_be_created(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $response = $this->actingAs($user)->post(route('leads.store'), [
            'name' => 'Anita Rao',
            'phone' => '9988776655',
            'source' => LeadSource::Instagram->value,
        ]);

        $lead = Lead::first();
        $response->assertRedirect(route('leads.show', $lead));
        $this->assertSame($company->id, $lead->company_id);
        $this->assertNull($lead->destination);
    }

    public function test_optional_lead_fields_can_be_omitted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post(route('leads.store'), [
            'name' => 'Vikram',
            'phone' => '9000000000',
            'source' => LeadSource::WalkIn->value,
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_lead_source_is_stored_correctly(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post(route('leads.store'), [
            'name' => 'Deepa',
            'phone' => '9111111111',
            'source' => LeadSource::Referral->value,
        ]);

        $this->assertSame(LeadSource::Referral, Lead::first()->source);
    }

    public function test_company_isolation_on_leads(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->get(route('leads.show', $leadB))->assertNotFound();
    }

    public function test_lead_conversion_creates_customer_and_marks_lead_converted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'name' => 'Sana',
            'phone' => '9222222222',
            'destination' => 'Goa',
        ]);

        $response = $this->actingAs($user)->post(route('leads.convert', $lead));

        $lead->refresh();
        $this->assertTrue($lead->isConverted());
        $this->assertNotNull($lead->converted_at);
        $this->assertSame('Sana', $lead->customer->name);
        $this->assertSame($company->id, $lead->customer->company_id);
        $response->assertRedirect(route('customers.show', $lead->customer));

        // Destination carried over into an auto-created enquiry.
        $this->assertSame('Goa', $lead->customer->enquiries->first()->destination);
    }

    public function test_lead_conversion_does_not_duplicate_customer_when_already_converted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = Lead::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('leads.convert', $lead));
        $firstCustomerId = $lead->fresh()->customer_id;

        $this->actingAs($user)->post(route('leads.convert', $lead->fresh()));

        $this->assertSame($firstCustomerId, $lead->fresh()->customer_id);
        $this->assertSame(1, \App\Models\Customer::count());
    }

    public function test_lead_conversion_preserves_original_lead_row(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = Lead::factory()->create(['company_id' => $company->id, 'source' => LeadSource::Facebook]);

        $this->actingAs($user)->post(route('leads.convert', $lead));

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'source' => 'facebook',
        ]);
    }

    public function test_lead_conversion_company_isolation(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('leads.convert', $leadB))->assertNotFound();
        $this->assertFalse($leadB->fresh()->isConverted());
    }
}

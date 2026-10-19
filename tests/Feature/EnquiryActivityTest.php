<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadConversionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class EnquiryActivityTest extends TestCase
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

    private function baseEnquiryPayload(Enquiry $enquiry, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $enquiry->customer_id,
            'destination' => $enquiry->destination,
            'status' => $enquiry->status->value,
        ], $overrides);
    }

    public function test_enquiry_show_page_loads(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id]);

        $this->actingAs($user)->get(route('enquiries.show', $enquiry))->assertOk();
    }

    public function test_enquiry_creation_logs_activity_and_appears_on_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company, 'Priya Sharma');
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ]);

        $enquiry = Enquiry::first();
        $activity = Activity::forSubject($enquiry)->first();

        $this->assertNotNull($activity);
        $this->assertSame('Enquiry for "Goa" created.', $activity->description);

        $this->actingAs($user)->get(route('enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('Enquiry for "Goa" created.')
            ->assertSee('Priya Sharma');
    }

    public function test_status_change_logs_activity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id, 'status' => EnquiryStatus::New]);

        $this->actingAs($user)->put(route('enquiries.update', $enquiry), $this->baseEnquiryPayload($enquiry, [
            'status' => EnquiryStatus::Planning->value,
        ]));

        $activity = Activity::forSubject($enquiry->fresh())->first();
        $this->assertNotNull($activity);
        $this->assertSame('Enquiry status changed from New to Planning.', $activity->description);
    }

    public function test_edit_without_status_change_does_not_create_activity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id, 'status' => EnquiryStatus::New, 'destination' => 'Goa']);

        $this->actingAs($user)->put(route('enquiries.update', $enquiry), $this->baseEnquiryPayload($enquiry, [
            'destination' => 'Kerala',
        ]));

        $this->assertSame(0, Activity::forSubject($enquiry->fresh())->count());
        $this->assertSame('Kerala', $enquiry->fresh()->destination);
    }

    public function test_activity_timestamp_is_displayed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id, 'status' => EnquiryStatus::New]);

        $this->actingAs($user)->put(route('enquiries.update', $enquiry), $this->baseEnquiryPayload($enquiry, [
            'status' => EnquiryStatus::Confirmed->value,
        ]));

        $activity = Activity::forSubject($enquiry->fresh())->first();

        $this->actingAs($user)->get(route('enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee($activity->created_at->diffForHumans());
    }

    public function test_multiple_activities_ordered_newest_first(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id]);

        $first = activity()->performedOn($enquiry)->causedBy($user)->log('First event.');
        $first->update(['created_at' => now()->subMinute()]);
        activity()->performedOn($enquiry)->causedBy($user)->log('Second event.');

        $content = $this->actingAs($user)->get(route('enquiries.show', $enquiry))->getContent();
        $firstPos = strpos($content, 'First event.');
        $secondPos = strpos($content, 'Second event.');

        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertLessThan($firstPos, $secondPos);
    }

    public function test_empty_activity_state_is_clean(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id]);

        $response = $this->actingAs($user)->get(route('enquiries.show', $enquiry));

        $response->assertOk()
            ->assertDontSee('undefined')
            ->assertDontSee('null');
    }

    public function test_company_a_cannot_access_company_b_enquiry_or_its_activity(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $enquiryB = Enquiry::factory()->create(['company_id' => $companyB->id, 'customer_id' => Customer::factory()->create(['company_id' => $companyB->id])->id, 'destination' => 'Secret Destination']);
        activity()->performedOn($enquiryB)->log('Enquiry for "Secret Destination" created.');

        $this->actingAs($userA)->get(route('enquiries.show', $enquiryB))->assertNotFound();
    }

    public function test_existing_enquiry_status_behavior_unchanged(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id, 'status' => EnquiryStatus::New]);

        $this->actingAs($user)->put(route('enquiries.update', $enquiry), $this->baseEnquiryPayload($enquiry, [
            'status' => EnquiryStatus::Lost->value,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertTrue($enquiry->fresh()->status === EnquiryStatus::Lost);
    }

    public function test_customer_relationship_remains_intact(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Linked Customer']);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        $this->actingAs($user)->get(route('enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('Linked Customer');
    }

    public function test_lead_conversion_creates_enquiry_activity_with_conversion_context(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = Lead::factory()->create(['company_id' => $company->id, 'destination' => 'Goa']);

        $this->actingAs($user);
        app(LeadConversionService::class)->convert($lead);

        $enquiry = Enquiry::first();
        $activity = Activity::forSubject($enquiry)->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('Lead conversion', $activity->description);
    }

    public function test_no_enquiry_activity_when_conversion_creates_no_enquiry(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = Lead::factory()->create(['company_id' => $company->id, 'destination' => null]);

        $this->actingAs($user);
        app(LeadConversionService::class)->convert($lead);

        $this->assertSame(0, Enquiry::count());
    }
}

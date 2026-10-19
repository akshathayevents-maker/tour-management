<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadConversionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CustomerActivityTest extends TestCase
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

    public function test_customer_show_page_loads(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('customers.show', $customer))->assertOk();
    }

    public function test_customer_creation_logs_activity_and_appears_on_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company, 'Priya Sharma');

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Ravi Kumar',
            'phone' => '9000011111',
        ]);

        $customer = Customer::first();
        $activity = Activity::forSubject($customer)->first();

        $this->assertNotNull($activity);
        $this->assertSame('Customer "Ravi Kumar" created.', $activity->description);

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Customer "Ravi Kumar" created.')
            ->assertSee('Priya Sharma');
    }

    public function test_activity_timestamp_is_displayed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post(route('customers.store'), ['name' => 'Ravi Kumar', 'phone' => '9000011111']);
        $customer = Customer::first();
        $activity = Activity::forSubject($customer)->first();

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee($activity->created_at->diffForHumans());
    }

    public function test_multiple_activities_ordered_newest_first(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $first = activity()->performedOn($customer)->causedBy($user)->log('First event.');
        $first->update(['created_at' => now()->subMinute()]);
        activity()->performedOn($customer)->causedBy($user)->log('Second event.');

        $content = $this->actingAs($user)->get(route('customers.show', $customer))->getContent();
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
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk()
            ->assertDontSee('undefined')
            ->assertDontSee('null');
        $this->assertSame(0, Activity::forSubject($customer)->count());
    }

    public function test_company_a_cannot_access_company_b_customer_or_its_activity(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Secret Customer']);
        activity()->performedOn($customerB)->log('Customer "Secret Customer" created.');

        $this->actingAs($userA)->get(route('customers.show', $customerB))->assertNotFound();
    }

    public function test_existing_customer_crud_unaffected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => 'Updated Name',
            'phone' => $customer->phone,
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertSame('Updated Name', $customer->fresh()->name);
    }

    public function test_lead_conversion_creates_customer_activity_with_conversion_context(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company, 'Priya Sharma');
        $lead = Lead::factory()->create(['company_id' => $company->id, 'name' => 'Converted Lead', 'destination' => null]);

        $this->actingAs($user);
        $customer = app(LeadConversionService::class)->convert($lead);

        $activity = Activity::forSubject($customer)->first();
        $this->assertNotNull($activity);
        $this->assertStringContainsString('Lead conversion', $activity->description);
    }

    public function test_repeated_edit_without_meaningful_change_does_not_create_duplicate_activity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Ravi Kumar']);

        $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => 'Ravi Kumar',
            'phone' => $customer->phone,
        ]);

        // Customer::update is not a logged event by design (only creation
        // is) — no activity should exist for a plain edit.
        $this->assertSame(0, Activity::forSubject($customer)->count());
    }
}

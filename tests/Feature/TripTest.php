<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripTest extends TestCase
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

    public function test_company_can_create_trip(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('trips.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ]);

        $trip = Trip::first();
        $response->assertRedirect(route('trips.show', $trip));
        $this->assertSame('Goa', $trip->destination);
        $this->assertSame($company->id, $trip->company_id);
        $this->assertNull($trip->start_date);
    }

    public function test_minimal_trip_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('trips.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Trip::count());
    }

    public function test_company_isolation(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = Trip::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->get(route('trips.show', $tripB))->assertNotFound();
        $this->actingAs($userA)->put(route('trips.update', $tripB), [
            'destination' => 'Hacked', 'status' => 'planning',
        ])->assertNotFound();

        $this->assertNotSame('Hacked', $tripB->fresh()->destination);
    }

    public function test_customer_relationship_is_correct(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('trips.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ]);

        $this->assertTrue(Trip::first()->customer->is($customer));
    }

    public function test_enquiry_relationship_is_correct(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        $this->actingAs($user)->post(route('trips.store'), [
            'customer_id' => $customer->id,
            'enquiry_id' => $enquiry->id,
            'destination' => 'Goa',
        ]);

        $this->assertTrue(Trip::first()->enquiry->is($enquiry));
    }

    public function test_cross_company_customer_cannot_be_attached(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('trips.store'), [
            'customer_id' => $customerB->id,
            'destination' => 'Goa',
        ])->assertSessionHasErrors('customer_id');

        $this->assertSame(0, Trip::count());
    }

    public function test_cross_company_enquiry_cannot_be_attached(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerA = Customer::factory()->create(['company_id' => $companyA->id]);
        $enquiryB = Enquiry::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('trips.store'), [
            'customer_id' => $customerA->id,
            'enquiry_id' => $enquiryB->id,
            'destination' => 'Goa',
        ])->assertSessionHasErrors('enquiry_id');

        $this->assertSame(0, Trip::count());
    }
}

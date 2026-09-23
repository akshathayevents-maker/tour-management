<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryTest extends TestCase
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

    public function test_minimal_enquiry_can_be_created(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ]);

        $enquiry = Enquiry::first();
        $response->assertRedirect(route('enquiries.show', $enquiry));
        $this->assertSame($company->id, $enquiry->company_id);
        $this->assertNull($enquiry->start_date);
    }

    public function test_optional_enquiry_fields_can_be_omitted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('enquiries.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Manali',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_enquiry_customer_must_belong_to_same_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('enquiries.store'), [
            'customer_id' => $customerB->id,
            'destination' => 'Goa',
        ])->assertSessionHasErrors('customer_id');

        $this->assertSame(0, Enquiry::count());
    }

    public function test_company_isolation_on_enquiries(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $enquiryB = Enquiry::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->get(route('enquiries.show', $enquiryB))->assertNotFound();
    }
}

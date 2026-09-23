<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    public function test_company_user_can_create_customer_with_minimum_fields(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $response = $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Rahul Kumar',
            'phone' => '9876543210',
        ]);

        $customer = Customer::first();
        $response->assertRedirect(route('customers.show', $customer));

        $this->assertNotNull($customer);
        $this->assertSame('Rahul Kumar', $customer->name);
        $this->assertSame($company->id, $customer->company_id);
        $this->assertNull($customer->email);
    }

    public function test_customer_creation_requires_name_and_phone(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)
            ->post(route('customers.store'), [])
            ->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_customer_can_be_created_with_optional_fields(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Priya Singh',
            'phone' => '9123456780',
            'email' => 'priya@example.com',
            'city' => 'Mumbai',
        ])->assertRedirect();

        $customer = Customer::first();
        $this->assertSame('priya@example.com', $customer->email);
        $this->assertSame('Mumbai', $customer->city);
    }

    public function test_company_a_cannot_view_company_b_customer(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('customers.show', $customerB))
            ->assertNotFound();
    }

    public function test_company_a_cannot_edit_company_b_customer(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->put(route('customers.update', $customerB), ['name' => 'Hacked', 'phone' => '000'])
            ->assertNotFound();

        $this->assertNotSame('Hacked', $customerB->fresh()->name);
    }

    public function test_customer_list_is_scoped_to_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $customerA = Customer::factory()->create(['company_id' => $companyA->id, 'name' => 'Own Customer']);
        Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Other Customer']);

        $this->actingAs($userA)->get(route('customers.index'))
            ->assertSee('Own Customer')
            ->assertDontSee('Other Customer');
    }
}

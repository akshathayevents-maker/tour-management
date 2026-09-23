<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
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

    public function test_company_can_create_supplier(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $response = $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => 'Sunrise Beach Resort',
        ]);

        $supplier = Supplier::first();
        $response->assertRedirect(route('suppliers.show', $supplier));
        $this->assertSame($company->id, $supplier->company_id);
        $this->assertNull($supplier->phone);
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post(route('suppliers.store'), [
            'name' => 'Local Driver',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_company_a_cannot_view_company_b_supplier(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->get(route('suppliers.show', $supplierB))->assertNotFound();
    }

    public function test_company_a_cannot_edit_company_b_supplier(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id, 'name' => 'Original']);

        $this->actingAs($userA)->put(route('suppliers.update', $supplierB), [
            'name' => 'Hacked',
        ])->assertNotFound();

        $this->assertSame('Original', $supplierB->fresh()->name);
    }

    public function test_supplier_list_is_scoped_to_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        Supplier::factory()->create(['company_id' => $companyA->id, 'name' => 'Own Supplier']);
        Supplier::factory()->create(['company_id' => $companyB->id, 'name' => 'Other Supplier']);

        $this->actingAs($userA)->get(route('suppliers.index'))
            ->assertSee('Own Supplier')
            ->assertDontSee('Other Supplier');
    }
}

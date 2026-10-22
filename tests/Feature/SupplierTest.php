<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Trip;
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

    private function bookingItemForSupplier(Company $company, Supplier $supplier, float $cost): BookingItem
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);

        return BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'supplier_id' => $supplier->id,
            'cost' => $cost,
        ]);
    }

    public function test_supplier_show_aggregates_cost_paid_and_payable_across_booking_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);

        $itemA = $this->bookingItemForSupplier($company, $supplier, 20000);
        $itemB = $this->bookingItemForSupplier($company, $supplier, 10000);

        SupplierPayment::factory()->create(['company_id' => $company->id, 'booking_item_id' => $itemA->id, 'amount' => 20000]);
        SupplierPayment::factory()->create(['company_id' => $company->id, 'booking_item_id' => $itemB->id, 'amount' => 4000]);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $this->assertSame(30000.0, $response->viewData('totalCost'));
        $this->assertSame(24000.0, $response->viewData('totalPaid'));
        $this->assertSame(6000.0, $response->viewData('totalPayable'));
    }

    public function test_supplier_show_only_counts_own_booking_items_payable_not_other_suppliers(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $supplierA = Supplier::factory()->create(['company_id' => $company->id]);
        $supplierB = Supplier::factory()->create(['company_id' => $company->id]);

        $this->bookingItemForSupplier($company, $supplierA, 10000);
        $this->bookingItemForSupplier($company, $supplierB, 50000);

        $response = $this->actingAs($user)->get(route('suppliers.show', $supplierA));

        $response->assertOk();
        $this->assertSame(10000.0, $response->viewData('totalCost'));
    }

    public function test_supplier_show_recent_payments_are_scoped_to_company(): void
    {
        $companyA = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $supplierA = Supplier::factory()->create(['company_id' => $companyA->id, 'name' => 'Visible Resort']);
        $itemA = $this->bookingItemForSupplier($companyA, $supplierA, 10000);
        SupplierPayment::factory()->create(['company_id' => $companyA->id, 'booking_item_id' => $itemA->id, 'amount' => 5000]);

        $response = $this->actingAs($userA)->get(route('suppliers.show', $supplierA));

        $response->assertOk();
        $recentPayments = $response->viewData('recentPayments');
        $this->assertCount(1, $recentPayments);
        $this->assertSame($itemA->id, $recentPayments->first()->bookingItem->id);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSupplierTest extends TestCase
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

    private function bookingForCompany(Company $company): Booking
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        return Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);
    }

    public function test_valid_same_company_supplier_is_accepted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Hotel', 'supplier_id' => $supplier->id,
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame($supplier->id, BookingItem::first()->supplier_id);
    }

    public function test_cross_company_supplier_is_rejected(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingA = $this->bookingForCompany($companyA);
        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('booking-items.store', $bookingA), [
            'description' => 'Hotel', 'supplier_id' => $supplierB->id,
        ])->assertSessionHasErrors('supplier_id');

        $this->assertSame(0, BookingItem::count());
    }

    public function test_inactive_supplier_does_not_appear_in_selection_list(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Active Resort', 'is_active' => true]);
        Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Retired Resort', 'is_active' => false]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);

        $this->actingAs($user)->get(route('bookings.show', $booking))
            ->assertSee('Active Resort')
            ->assertDontSee('Retired Resort');
    }

    public function test_existing_booking_item_referencing_inactive_supplier_remains_valid(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Sunrise Resort']);
        $item = BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'supplier_id' => $supplier->id]);

        $supplier->update(['is_active' => false]);

        $this->assertSame($supplier->id, $item->fresh()->supplier_id);
        $this->actingAs($user)->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('Sunrise Resort');
    }
}

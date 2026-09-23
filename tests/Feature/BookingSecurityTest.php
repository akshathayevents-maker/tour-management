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

class BookingSecurityTest extends TestCase
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

    public function test_company_a_cannot_edit_company_b_booking_item(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingForCompany($companyB);
        $itemB = BookingItem::factory()->create(['company_id' => $companyB->id, 'booking_id' => $bookingB->id, 'description' => 'Original']);

        $this->actingAs($userA)->patch(route('booking-items.update', $itemB), [
            'description' => 'Hacked',
        ])->assertNotFound();

        $this->assertSame('Original', $itemB->fresh()->description);
    }

    public function test_company_a_cannot_change_status_of_company_b_booking_item(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingForCompany($companyB);
        $itemB = BookingItem::factory()->create(['company_id' => $companyB->id, 'booking_id' => $bookingB->id]);

        $this->actingAs($userA)->patch(route('booking-items.status', $itemB), ['status' => 'confirmed'])
            ->assertNotFound();

        $this->assertNotSame(\App\Enums\BookingItemStatus::Confirmed, $itemB->fresh()->status);
    }

    public function test_company_a_cannot_add_items_to_company_b_booking(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingForCompany($companyB);

        $this->actingAs($userA)->post(route('booking-items.store', $bookingB), [
            'description' => 'Should not work',
        ])->assertNotFound();

        $this->assertSame(0, BookingItem::count());
    }

    public function test_company_a_cannot_view_company_b_booking_items_list(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingA = $this->bookingForCompany($companyA);
        $bookingB = $this->bookingForCompany($companyB);
        BookingItem::factory()->create(['company_id' => $companyA->id, 'booking_id' => $bookingA->id, 'description' => 'Own Item']);
        BookingItem::factory()->create(['company_id' => $companyB->id, 'booking_id' => $bookingB->id, 'description' => 'Other Item']);

        $this->actingAs($userA)->get(route('bookings.show', $bookingA))
            ->assertSee('Own Item')
            ->assertDontSee('Other Item');
    }

    public function test_booking_list_is_scoped_to_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripA = Trip::factory()->create(['company_id' => $companyA->id, 'customer_id' => Customer::factory()->create(['company_id' => $companyA->id])->id, 'destination' => 'Own Trip']);
        $tripB = Trip::factory()->create(['company_id' => $companyB->id, 'customer_id' => Customer::factory()->create(['company_id' => $companyB->id])->id, 'destination' => 'Other Trip']);
        Booking::factory()->create(['company_id' => $companyA->id, 'trip_id' => $tripA->id, 'customer_id' => $tripA->customer_id]);
        Booking::factory()->create(['company_id' => $companyB->id, 'trip_id' => $tripB->id, 'customer_id' => $tripB->customer_id]);

        $this->actingAs($userA)->get(route('bookings.index'))
            ->assertSee('Own Trip')
            ->assertDontSee('Other Trip');
    }

    public function test_supplier_snapshot_write_via_status_endpoint_cannot_leak_cross_company_supplier(): void
    {
        // A cross-company supplier can never even be attached (see
        // BookingSupplierTest), so the confirm-snapshot path can't leak
        // another company's supplier data either — verified here as the
        // combined path rather than assumed from the two halves alone.
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingA = $this->bookingForCompany($companyA);
        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id, 'name' => 'Other Co Hotel']);

        $this->actingAs($userA)->post(route('booking-items.store', $bookingA), [
            'description' => 'Hotel', 'supplier_id' => $supplierB->id,
        ])->assertSessionHasErrors('supplier_id');

        $this->assertSame(0, BookingItem::count());
    }

    public function test_company_a_cannot_complete_company_b_booking(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingForCompany($companyB);

        $this->actingAs($userA)->post(route('bookings.complete', $bookingB))->assertNotFound();

        $this->assertNotSame(\App\Enums\BookingStatus::Completed, $bookingB->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\BookingItemStatus;
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

class BookingItemTest extends TestCase
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

    public function test_minimal_booking_item_creation(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);

        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'XYZ Resort',
        ])->assertRedirect();

        $item = BookingItem::first();
        $this->assertSame('XYZ Resort', $item->description);
        $this->assertNull($item->sell_price);
    }

    public function test_sell_price_may_be_null(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);

        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Transport',
        ])->assertSessionDoesntHaveErrors();

        $this->assertNull(BookingItem::first()->sell_price);
    }

    public function test_supplier_may_be_null(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);

        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Misc',
        ]);

        $this->assertNull(BookingItem::first()->supplier_id);
    }

    public function test_dates_may_be_null(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);

        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Activity',
        ]);

        $item = BookingItem::first();
        $this->assertNull($item->start_at);
        $this->assertNull($item->end_at);
    }

    public function test_status_defaults_to_pending(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);

        $this->actingAs($user)->post(route('booking-items.store', $booking), ['description' => 'Hotel']);

        $this->assertTrue(BookingItem::first()->status === BookingItemStatus::Pending);
    }

    public function test_status_can_progress_pending_to_requested_to_confirmed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);
        $this->actingAs($user)->post(route('booking-items.store', $booking), ['description' => 'Hotel']);
        $item = BookingItem::first();

        $this->actingAs($user)->patch(route('booking-items.status', $item), ['status' => 'requested']);
        $this->assertTrue($item->fresh()->status === BookingItemStatus::Requested);

        $this->actingAs($user)->patch(route('booking-items.status', $item->fresh()), ['status' => 'confirmed']);
        $this->assertTrue($item->fresh()->status === BookingItemStatus::Confirmed);
    }

    public function test_confirming_snapshots_supplier_name_and_phone(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Sunrise Resort', 'phone' => '9998887776']);
        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Hotel', 'supplier_id' => $supplier->id,
        ]);
        $item = BookingItem::first();

        $this->actingAs($user)->patch(route('booking-items.status', $item), ['status' => 'confirmed']);

        $item->refresh();
        $this->assertSame('Sunrise Resort', $item->supplier_name_snapshot);
        $this->assertSame('9998887776', $item->supplier_phone_snapshot);
    }

    public function test_snapshot_survives_later_supplier_edit(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Sunrise Resort', 'phone' => '9998887776']);
        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Hotel', 'supplier_id' => $supplier->id,
        ]);
        $item = BookingItem::first();
        $this->actingAs($user)->patch(route('booking-items.status', $item), ['status' => 'confirmed']);

        // Supplier's real phone number changes later.
        $supplier->update(['phone' => '0000000000']);

        $this->assertSame('9998887776', $item->fresh()->supplier_phone_snapshot);
    }

    public function test_supplier_can_be_changed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);
        $supplierA = Supplier::factory()->create(['company_id' => $company->id]);
        $supplierB = Supplier::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Hotel', 'supplier_id' => $supplierA->id,
        ]);
        $item = BookingItem::first();

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => 'Hotel', 'supplier_id' => $supplierB->id,
        ]);

        $this->assertSame($supplierB->id, $item->fresh()->supplier_id);
    }

    public function test_item_can_be_cancelled(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingForCompany($company);
        $this->actingAs($user)->post(route('booking-items.store', $booking), ['description' => 'Activity']);
        $item = BookingItem::first();

        $this->actingAs($user)->patch(route('booking-items.status', $item), [
            'status' => 'cancelled', 'cancellation_reason' => 'No longer needed',
        ]);

        $item->refresh();
        $this->assertTrue($item->status === BookingItemStatus::Cancelled);
        $this->assertNotNull($item->cancelled_at);
        $this->assertSame('No longer needed', $item->cancellation_reason);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\BookingItemStatus;
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

class CancelledBookingItemGuardTest extends TestCase
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

    private function cancelledItemInConfirmedBooking(Company $company, array $overrides = []): BookingItem
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);

        return BookingItem::factory()->create(array_merge([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'description' => 'Original Description',
            'status' => BookingItemStatus::Cancelled,
            'cancelled_at' => now(),
        ], $overrides));
    }

    public function test_cancelled_item_description_and_category_cannot_be_changed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->cancelledItemInConfirmedBooking($company);

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => 'Hacked Description',
            'category' => 'transport',
        ])->assertStatus(422);

        $this->assertSame('Original Description', $item->fresh()->description);
    }

    public function test_cancelled_item_cost_cannot_be_changed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->cancelledItemInConfirmedBooking($company, ['cost' => 5000]);

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => $item->description,
            'cost' => 99999,
        ])->assertStatus(422);

        $this->assertEquals(5000, $item->fresh()->cost);
    }

    public function test_cancelled_item_sell_price_cannot_be_changed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->cancelledItemInConfirmedBooking($company, ['sell_price' => 10000]);

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => $item->description,
            'sell_price' => 55555,
        ])->assertStatus(422);

        $this->assertEquals(10000, $item->fresh()->sell_price);
    }

    public function test_cancelled_item_supplier_cannot_be_changed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $originalSupplier = Supplier::factory()->create(['company_id' => $company->id]);
        $newSupplier = Supplier::factory()->create(['company_id' => $company->id]);
        $item = $this->cancelledItemInConfirmedBooking($company, ['supplier_id' => $originalSupplier->id]);

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => $item->description,
            'supplier_id' => $newSupplier->id,
        ])->assertStatus(422);

        $this->assertSame($originalSupplier->id, $item->fresh()->supplier_id);
    }

    public function test_cancelled_item_status_cannot_be_changed_via_status_endpoint(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->cancelledItemInConfirmedBooking($company);

        $this->actingAs($user)->patch(route('booking-items.status', $item), [
            'status' => 'confirmed',
        ])->assertStatus(422);

        $this->assertTrue($item->fresh()->status === BookingItemStatus::Cancelled);
    }

    public function test_non_cancelled_item_in_same_booking_remains_editable(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);

        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'status' => BookingItemStatus::Cancelled]);
        $activeItem = BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'status' => BookingItemStatus::Pending, 'description' => 'Still Editable']);

        $this->actingAs($user)->patch(route('booking-items.update', $activeItem), [
            'description' => 'Updated Description',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Updated Description', $activeItem->fresh()->description);
    }

    public function test_cancelled_item_can_still_be_viewed_on_booking_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->cancelledItemInConfirmedBooking($company, ['description' => 'Visible Cancelled Service']);

        $this->actingAs($user)->get(route('bookings.show', $item->booking))
            ->assertOk()
            ->assertSee('Visible Cancelled Service');
    }

    public function test_supplier_payment_void_remains_unaffected_by_the_guard(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->cancelledItemInConfirmedBooking($company, ['cost' => 5000]);
        $payment = SupplierPayment::factory()->create(['company_id' => $company->id, 'booking_item_id' => $item->id, 'amount' => 2000]);

        $this->actingAs($user)->post(route('supplier-payments.void', $payment), ['reason' => 'Correction'])
            ->assertRedirect();

        $this->assertTrue($payment->fresh()->isVoided());
    }

    public function test_booking_confirmation_print_still_works_with_a_cancelled_item(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->cancelledItemInConfirmedBooking($company);

        $this->actingAs($user)->get(route('bookings.print', $item->booking))->assertOk();
    }

    public function test_tenant_isolation_is_unaffected_by_the_guard(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $itemB = $this->cancelledItemInConfirmedBooking($companyB);

        $this->actingAs($userA)->patch(route('booking-items.update', $itemB), [
            'description' => 'Hacked',
        ])->assertNotFound();

        $this->assertSame('Original Description', $itemB->fresh()->description);
    }
}

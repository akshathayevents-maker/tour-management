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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingLockTest extends TestCase
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

    private function tripForCompany(Company $company): Trip
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        return Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
    }

    private function completedBooking(Company $company, User $user): Booking
    {
        $trip = $this->tripForCompany($company);
        $this->actingAs($user)->post(route('bookings.store'), ['customer_id' => $trip->customer_id, 'trip_id' => $trip->id]);
        $booking = Booking::first();
        $this->actingAs($user)->post(route('bookings.complete', $booking));

        return $booking->fresh();
    }

    private function cancelledBooking(Company $company, User $user): Booking
    {
        $trip = $this->tripForCompany($company);
        $this->actingAs($user)->post(route('bookings.store'), ['customer_id' => $trip->customer_id, 'trip_id' => $trip->id]);
        $booking = Booking::first();
        $this->actingAs($user)->post(route('bookings.cancel', $booking));

        return $booking->fresh();
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function lockedBookingProvider(): iterable
    {
        yield 'completed' => ['completedBooking'];
        yield 'cancelled' => ['cancelledBooking'];
    }

    #[DataProvider('lockedBookingProvider')]
    public function test_cannot_complete_or_cancel_again(string $factory): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->{$factory}($company, $user);

        $this->actingAs($user)->post(route('bookings.complete', $booking))->assertStatus(422);
        $this->actingAs($user)->post(route('bookings.cancel', $booking))->assertStatus(422);
    }

    #[DataProvider('lockedBookingProvider')]
    public function test_cannot_create_booking_item(string $factory): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->{$factory}($company, $user);

        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Should not work',
        ])->assertStatus(422);

        $this->assertSame(0, BookingItem::count());
    }

    #[DataProvider('lockedBookingProvider')]
    public function test_cannot_edit_booking_item(string $factory): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->{$factory}($company, $user);
        $item = BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'description' => 'Original']);

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => 'Hacked',
        ])->assertStatus(422);

        $this->assertSame('Original', $item->fresh()->description);
    }

    #[DataProvider('lockedBookingProvider')]
    public function test_cannot_change_booking_item_status(string $factory): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->{$factory}($company, $user);
        $item = BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);

        $this->actingAs($user)->patch(route('booking-items.status', $item), ['status' => 'confirmed'])
            ->assertStatus(422);

        $this->assertTrue($item->fresh()->status === BookingItemStatus::Pending);
    }

    #[DataProvider('lockedBookingProvider')]
    public function test_cannot_change_booking_item_supplier(string $factory): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->{$factory}($company, $user);
        $supplierA = Supplier::factory()->create(['company_id' => $company->id]);
        $supplierB = Supplier::factory()->create(['company_id' => $company->id]);
        $item = BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'supplier_id' => $supplierA->id]);

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => $item->description, 'supplier_id' => $supplierB->id,
        ])->assertStatus(422);

        $this->assertSame($supplierA->id, $item->fresh()->supplier_id);
    }

    #[DataProvider('lockedBookingProvider')]
    public function test_cannot_cancel_booking_item(string $factory): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->{$factory}($company, $user);
        $item = BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);

        $this->actingAs($user)->patch(route('booking-items.status', $item), ['status' => 'cancelled'])
            ->assertStatus(422);

        $this->assertNull($item->fresh()->cancelled_at);
    }

    #[DataProvider('lockedBookingProvider')]
    public function test_checklist_item_can_still_be_completed(string $factory): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->{$factory}($company, $user);
        $checklistItem = $booking->checklistItems()->where('label', 'Customer feedback requested')->first();

        $this->actingAs($user)->patch(route('booking-checklist-items.complete', $checklistItem))
            ->assertRedirect();

        $this->assertNotNull($checklistItem->fresh()->done_at);
    }

    #[DataProvider('lockedBookingProvider')]
    public function test_custom_checklist_item_can_still_be_added(string $factory): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->{$factory}($company, $user);

        $this->actingAs($user)->post(route('booking-checklist-items.store', $booking), [
            'label' => 'Review requested',
        ])->assertSessionDoesntHaveErrors();

        $this->assertTrue($booking->checklistItems()->where('label', 'Review requested')->exists());
    }

    public function test_confirmed_booking_still_allows_normal_operations(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);
        $this->actingAs($user)->post(route('bookings.store'), ['customer_id' => $trip->customer_id, 'trip_id' => $trip->id]);
        $booking = Booking::first();

        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Hotel',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, $booking->fresh()->items->count());
    }
}

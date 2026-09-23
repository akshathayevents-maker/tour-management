<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingChecklistItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingChecklistTest extends TestCase
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

    public function test_defaults_automatically_created_on_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);

        $this->actingAs($user)->post(route('bookings.store'), ['customer_id' => $trip->customer_id, 'trip_id' => $trip->id]);
        $booking = Booking::first();

        $this->assertGreaterThanOrEqual(5, $booking->checklistItems()->count());
        $this->assertTrue($booking->checklistItems()->where('phase', 'before_trip')->exists());
        $this->assertTrue($booking->checklistItems()->where('phase', 'during_trip')->exists());
        $this->assertTrue($booking->checklistItems()->where('phase', 'after_trip')->exists());
    }

    public function test_custom_item_can_be_added_with_only_a_label(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $trip->customer_id]);

        $this->actingAs($user)->post(route('booking-checklist-items.store', $booking), [
            'label' => 'Confirm dietary requirements',
        ])->assertSessionDoesntHaveErrors();

        $this->assertTrue($booking->checklistItems()->where('label', 'Confirm dietary requirements')->exists());
    }

    public function test_item_can_be_completed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $trip->customer_id]);
        $item = BookingChecklistItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);

        $this->actingAs($user)->patch(route('booking-checklist-items.complete', $item))->assertRedirect();

        $this->assertNotNull($item->fresh()->done_at);
    }

    public function test_completed_by_is_recorded(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $trip->customer_id]);
        $item = BookingChecklistItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id]);

        $this->actingAs($user)->patch(route('booking-checklist-items.complete', $item));

        $this->assertSame($user->id, $item->fresh()->done_by);
    }

    public function test_tenant_isolation_on_checklist(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = $this->tripForCompany($companyB);
        $bookingB = Booking::factory()->create(['company_id' => $companyB->id, 'trip_id' => $tripB->id, 'customer_id' => $tripB->customer_id]);
        $itemB = BookingChecklistItem::factory()->create(['company_id' => $companyB->id, 'booking_id' => $bookingB->id]);

        $this->actingAs($userA)->post(route('booking-checklist-items.store', $bookingB), ['label' => 'Hacked'])
            ->assertNotFound();
        $this->actingAs($userA)->patch(route('booking-checklist-items.complete', $itemB))
            ->assertNotFound();

        $this->assertNull($itemB->fresh()->done_at);
    }
}

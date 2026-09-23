<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
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

    public function test_minimal_booking_creation(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'customer_id' => $trip->customer_id,
            'trip_id' => $trip->id,
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame($company->id, $booking->company_id);
        $this->assertTrue($booking->status === BookingStatus::Confirmed);
    }

    public function test_direct_booking_creation_seeds_default_checklist(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);

        $this->actingAs($user)->post(route('bookings.store'), [
            'customer_id' => $trip->customer_id,
            'trip_id' => $trip->id,
        ]);

        $booking = Booking::first();
        $this->assertGreaterThan(0, $booking->checklistItems()->count());
    }

    public function test_booking_requires_customer(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);

        $this->actingAs($user)->post(route('bookings.store'), [
            'trip_id' => $trip->id,
        ])->assertSessionHasErrors('customer_id');

        $this->assertSame(0, Booking::count());
    }

    public function test_booking_requires_trip(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);

        $this->actingAs($user)->post(route('bookings.store'), [
            'customer_id' => $trip->customer_id,
        ])->assertSessionHasErrors('trip_id');

        $this->assertSame(0, Booking::count());
    }

    public function test_booking_can_exist_without_quotation(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);

        $this->actingAs($user)->post(route('bookings.store'), [
            'customer_id' => $trip->customer_id,
            'trip_id' => $trip->id,
        ]);

        $this->assertNull(Booking::first()->accepted_quotation_version_id);
    }

    public function test_cross_company_customer_cannot_be_attached(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripA = $this->tripForCompany($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('bookings.store'), [
            'customer_id' => $customerB->id,
            'trip_id' => $tripA->id,
        ])->assertSessionHasErrors('customer_id');

        $this->assertSame(0, Booking::count());
    }

    public function test_cross_company_trip_cannot_be_attached(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripA = $this->tripForCompany($companyA);
        $tripB = $this->tripForCompany($companyB);

        $this->actingAs($userA)->post(route('bookings.store'), [
            'customer_id' => $tripA->customer_id,
            'trip_id' => $tripB->id,
        ])->assertSessionHasErrors('trip_id');

        $this->assertSame(0, Booking::count());
    }

    public function test_company_a_cannot_view_company_b_booking(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = $this->tripForCompany($companyB);
        $bookingB = Booking::factory()->create(['company_id' => $companyB->id, 'trip_id' => $tripB->id, 'customer_id' => $tripB->customer_id]);

        $this->actingAs($userA)->get(route('bookings.show', $bookingB))->assertNotFound();
    }

    public function test_company_a_cannot_cancel_company_b_booking(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = $this->tripForCompany($companyB);
        $bookingB = Booking::factory()->create(['company_id' => $companyB->id, 'trip_id' => $tripB->id, 'customer_id' => $tripB->customer_id]);

        $this->actingAs($userA)->post(route('bookings.cancel', $bookingB))->assertNotFound();

        $this->assertTrue($bookingB->fresh()->status === BookingStatus::Confirmed);
    }

    public function test_confirmed_booking_can_be_cancelled(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);
        $this->actingAs($user)->post(route('bookings.store'), ['customer_id' => $trip->customer_id, 'trip_id' => $trip->id]);
        $booking = Booking::first();

        $this->actingAs($user)->post(route('bookings.cancel', $booking), ['cancellation_reason' => 'Customer changed plans'])
            ->assertRedirect();

        $booking->refresh();
        $this->assertTrue($booking->status === BookingStatus::Cancelled);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertSame('Customer changed plans', $booking->cancellation_reason);
    }

    public function test_confirmed_booking_can_be_completed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);
        $this->actingAs($user)->post(route('bookings.store'), ['customer_id' => $trip->customer_id, 'trip_id' => $trip->id]);
        $booking = Booking::first();

        $this->actingAs($user)->post(route('bookings.complete', $booking))->assertRedirect();

        $booking->refresh();
        $this->assertTrue($booking->status === BookingStatus::Completed);
        $this->assertNotNull($booking->completed_at);
    }

    public function test_cancelled_booking_cannot_be_completed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);
        $this->actingAs($user)->post(route('bookings.store'), ['customer_id' => $trip->customer_id, 'trip_id' => $trip->id]);
        $booking = Booking::first();
        $this->actingAs($user)->post(route('bookings.cancel', $booking));

        $this->actingAs($user)->post(route('bookings.complete', $booking->fresh()))
            ->assertStatus(422);

        $this->assertTrue($booking->fresh()->status === BookingStatus::Cancelled);
    }

    public function test_completed_booking_cannot_be_cancelled(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripForCompany($company);
        $this->actingAs($user)->post(route('bookings.store'), ['customer_id' => $trip->customer_id, 'trip_id' => $trip->id]);
        $booking = Booking::first();
        $this->actingAs($user)->post(route('bookings.complete', $booking));

        $this->actingAs($user)->post(route('bookings.cancel', $booking->fresh()))
            ->assertStatus(422);

        $this->assertTrue($booking->fresh()->status === BookingStatus::Completed);
    }
}

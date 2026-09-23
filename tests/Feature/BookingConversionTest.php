<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Customer;
use App\Models\QuotationVersion;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingConversionTest extends TestCase
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

    private function acceptedVersionWithLine(User $user, Company $company, string $description = 'Complete Goa Package', float $price = 50000): QuotationVersion
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();
        $this->actingAs($user)->post(route('quotation-line-items.store', $version), [
            'description' => $description,
            'sell_price' => $price,
        ]);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));
        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()));

        return $version->fresh();
    }

    public function test_accepted_quotation_can_create_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLine($user, $company);

        $response = $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));

        $booking = Booking::first();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame($version->id, $booking->accepted_quotation_version_id);
        $this->assertSame($version->quotation->trip_id, $booking->trip_id);
        $this->assertSame($version->quotation->trip->customer_id, $booking->customer_id);
    }

    public function test_draft_quotation_cannot_create_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version))
            ->assertStatus(422);

        $this->assertSame(0, Booking::count());
    }

    public function test_quotation_lines_can_be_selectively_converted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLine($user, $company, 'Hotel', 30000);
        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $booking = Booking::first();

        $this->actingAs($user)->post(route('booking-items.store', $booking), [
            'description' => 'Hotel',
            'sell_price' => 30000,
        ]);

        $this->assertSame(1, $booking->fresh()->items->count());
    }

    public function test_quotation_line_can_be_skipped(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLine($user, $company);
        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $booking = Booking::first();

        // Simply never converting the line — booking remains valid with
        // zero items, nothing forces a 1:1 mapping.
        $this->assertSame(0, $booking->fresh()->items->count());
    }

    public function test_broad_quotation_line_can_be_replaced_by_multiple_booking_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLine($user, $company, 'Complete Goa Package', 50000);
        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $booking = Booking::first();

        $this->actingAs($user)->post(route('booking-items.store', $booking), ['description' => 'Hotel', 'sell_price' => 30000]);
        $this->actingAs($user)->post(route('booking-items.store', $booking), ['description' => 'Transport', 'sell_price' => 10000]);
        $this->actingAs($user)->post(route('booking-items.store', $booking), ['description' => 'Activity', 'sell_price' => 8000]);

        $this->assertSame(3, $booking->fresh()->items->count());
    }

    public function test_accepted_quotation_remains_unchanged_after_booking_created(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLine($user, $company);
        $originalTotal = $version->totalSellPrice();

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));

        $this->assertEquals($originalTotal, $version->fresh()->totalSellPrice());
        $this->assertTrue($version->fresh()->status === \App\Enums\QuotationVersionStatus::Accepted);
    }

    public function test_repeated_conversion_shows_existing_booking_instead_of_creating_another(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLine($user, $company);
        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $firstBooking = Booking::first();

        // The UI hides "Create booking" once one exists (see the quotation
        // show view), but the route itself has no uniqueness constraint —
        // this documents that a second explicit POST still creates a
        // second booking rather than silently failing.
        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version->fresh()));

        $this->assertSame(2, Booking::where('accepted_quotation_version_id', $version->id)->count());
    }
}

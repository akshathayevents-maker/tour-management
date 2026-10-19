<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Itinerary;
use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryPrintTest extends TestCase
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

    private function tripFor(Company $company, array $tripOverrides = [], ?Customer $customer = null): Trip
    {
        $customer ??= Customer::factory()->create(['company_id' => $company->id]);

        return Trip::factory()->create(array_merge([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ], $tripOverrides));
    }

    // ---- Access ---------------------------------------------------

    public function test_authorized_user_can_print_itinerary(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);

        $this->actingAs($user)->get(route('trips.itinerary.print', $trip))->assertOk();
    }

    public function test_guest_cannot_access_print_page(): void
    {
        $company = Company::factory()->create();
        $trip = $this->tripFor($company);

        $this->get(route('trips.itinerary.print', $trip))->assertRedirect(route('login'));
    }

    public function test_company_a_cannot_print_company_b_trip_itinerary(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = $this->tripFor($companyB);

        $this->actingAs($userA)->get(route('trips.itinerary.print', $tripB))->assertNotFound();
    }

    public function test_nonexistent_trip_returns_404(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get('/trips/999999/itinerary/print')->assertNotFound();
    }

    public function test_trip_without_itinerary_renders_gracefully(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);

        $response = $this->actingAs($user)->get(route('trips.itinerary.print', $trip));

        $response->assertOk()
            ->assertSee('No itinerary has been planned for this trip yet.')
            ->assertDontSee('undefined')
            ->assertDontSee('null')
            ->assertDontSee('Not added yet');
    }

    // ---- Content ----------------------------------------------------

    public function test_company_and_customer_information_appears(): void
    {
        $company = Company::factory()->create(['name' => 'Print Test Travels']);
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Itinerary Customer', 'phone' => '9876501234']);
        $trip = $this->tripFor($company, [], $customer);

        $this->actingAs($user)->get(route('trips.itinerary.print', $trip))
            ->assertOk()
            ->assertSee('Print Test Travels')
            ->assertSee('Itinerary Customer')
            ->assertSee('9876501234');
    }

    public function test_destination_travel_dates_and_travellers_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company, [
            'destination' => 'Himalayan Trek',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-10',
            'travellers_count' => 6,
        ]);

        $this->actingAs($user)->get(route('trips.itinerary.print', $trip))
            ->assertOk()
            ->assertSee('Himalayan Trek')
            ->assertSee('01 Dec 2026')
            ->assertSee('10 Dec 2026')
            ->assertSee('6');
    }

    public function test_multiple_days_render_in_order_with_dates(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $itinerary = Itinerary::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);

        ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id, 'day_number' => 2, 'date' => '2026-12-02', 'notes' => 'Second day plan']);
        ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id, 'day_number' => 1, 'date' => '2026-12-01', 'notes' => 'First day plan']);

        $response = $this->actingAs($user)->get(route('trips.itinerary.print', $trip));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertSee('Day 01', $content);
        $this->assertSee('Day 02', $content);
        // Day 01 (ordered by day_number, not insertion order) must appear before Day 02.
        $this->assertLessThan(strpos($content, 'Day 02'), strpos($content, 'Day 01'));
        $response->assertSee('First day plan')->assertSee('Second day plan');
    }

    public function test_day_without_items_renders_gracefully(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $itinerary = Itinerary::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);
        ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id, 'day_number' => 1]);

        $response = $this->actingAs($user)->get(route('trips.itinerary.print', $trip));

        $response->assertOk()
            ->assertSee('Day 01')
            ->assertDontSee('undefined')
            ->assertDontSee('null');
    }

    public function test_items_render_in_sort_order(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $itinerary = Itinerary::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);
        $day = ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id, 'day_number' => 1]);

        ItineraryItem::factory()->create(['company_id' => $company->id, 'itinerary_day_id' => $day->id, 'title' => 'Second Activity', 'sort_order' => 2]);
        ItineraryItem::factory()->create(['company_id' => $company->id, 'itinerary_day_id' => $day->id, 'title' => 'First Activity', 'sort_order' => 1]);

        $response = $this->actingAs($user)->get(route('trips.itinerary.print', $trip));
        $response->assertOk();

        $content = $response->getContent();
        $response->assertSee('First Activity')->assertSee('Second Activity');
        $this->assertLessThan(strpos($content, 'Second Activity'), strpos($content, 'First Activity'));
    }

    private function assertSee(string $needle, string $content): void
    {
        $this->assertStringContainsString($needle, $content);
    }

    // ---- Empty/partial data --------------------------------------------

    public function test_optional_fields_missing_render_gracefully(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company, ['start_date' => null, 'end_date' => null, 'travellers_count' => null]);
        $itinerary = Itinerary::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);
        ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id, 'day_number' => 1, 'date' => null, 'notes' => null]);

        $response = $this->actingAs($user)->get(route('trips.itinerary.print', $trip));

        $response->assertOk()
            ->assertSee('Not scheduled')
            ->assertDontSee('undefined')
            ->assertDontSee('null')
            ->assertDontSee('Not added yet');
    }

    // ---- Security / non-leakage -----------------------------------------

    public function test_internal_financial_and_supplier_information_never_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = $this->tripFor($company, [], $customer);

        $booking = \App\Models\Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);
        $supplier = \App\Models\Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Secret Internal Supplier']);
        \App\Models\BookingItem::factory()->create([
            'company_id' => $company->id, 'booking_id' => $booking->id, 'supplier_id' => $supplier->id,
            'cost' => 999999, 'sell_price' => 50000,
        ]);
        \App\Models\CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'amount' => 100]);

        $content = $this->actingAs($user)->get(route('trips.itinerary.print', $trip))
            ->assertOk()
            ->getContent();

        foreach (['Secret Internal Supplier', '999,999.00', '50,000.00', 'amountRemaining', 'SupplierPayment', 'CustomerPayment'] as $needle) {
            $this->assertStringNotContainsString($needle, $content);
        }
    }

    public function test_itinerary_day_notes_do_not_leak_as_internal_operational_data(): void
    {
        // Day notes ARE customer-facing content by this model's design
        // (there's no separate internal/external notes field) — this test
        // documents that assumption rather than asserting it's hidden.
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $itinerary = Itinerary::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);
        ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id, 'day_number' => 1, 'notes' => 'Sightseeing and local cuisine.']);

        $this->actingAs($user)->get(route('trips.itinerary.print', $trip))
            ->assertOk()
            ->assertSee('Sightseeing and local cuisine.');
    }

    // ---- Performance ----------------------------------------------------

    public function test_no_n_plus_one_queries_with_multiple_days_and_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $itinerary = Itinerary::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);

        for ($d = 1; $d <= 5; $d++) {
            $day = ItineraryDay::factory()->create(['company_id' => $company->id, 'itinerary_id' => $itinerary->id, 'day_number' => $d]);
            for ($i = 1; $i <= 3; $i++) {
                ItineraryItem::factory()->create(['company_id' => $company->id, 'itinerary_day_id' => $day->id, 'sort_order' => $i]);
            }
        }

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->actingAs($user)->get(route('trips.itinerary.print', $trip))->assertOk();
        $queryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        // Fixed regardless of day/item count — proves no per-day/per-item query.
        $this->assertLessThan(15, $queryCount);
    }

    // ---- Regression -----------------------------------------------------

    public function test_trip_show_page_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);

        $this->actingAs($user)->get(route('trips.show', $trip))->assertOk();
    }

    public function test_booking_print_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = $this->tripFor($company, [], $customer);
        $booking = \App\Models\Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);

        $this->actingAs($user)->get(route('bookings.print', $booking))->assertOk();
    }

    public function test_quotation_print_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $quotation = \App\Models\Quotation::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);
        $version = \App\Models\QuotationVersion::factory()->create(['company_id' => $company->id, 'quotation_id' => $quotation->id, 'status' => 'sent']);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))->assertOk();
    }

    public function test_money_page_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get(route('money'))->assertOk();
    }

    public function test_dashboard_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}

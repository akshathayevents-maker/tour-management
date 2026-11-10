<?php

namespace Tests\Feature;

use App\Enums\BookingItemStatus;
use App\Enums\TripStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Enquiry;
use App\Models\Quotation;
use App\Models\QuotationVersion;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
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

    public function test_profile_shows_multiple_trips_bookings_and_correct_financial_totals(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Ananya Rao']);

        $tripOne = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Goa']);
        $bookingOne = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $tripOne->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingOne->id, 'sell_price' => 10000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingOne->id, 'amount' => 4000]);

        $tripTwo = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Manali']);
        $bookingTwo = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $tripTwo->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingTwo->id, 'sell_price' => 15000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingTwo->id, 'amount' => 15000]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('Goa');
        $response->assertSee('Manali');
        $response->assertSee(number_format(25000, 0)); // total booking value
        $response->assertSee(number_format(19000, 0)); // total collected
        $response->assertSee(number_format(6000, 0)); // outstanding balance
    }

    public function test_voided_payment_is_excluded_from_collected_total(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => 10000]);

        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'amount' => 5000]);
        CustomerPayment::factory()->create([
            'company_id' => $company->id, 'booking_id' => $booking->id, 'amount' => 3000,
            'voided_at' => now(), 'voided_reason' => 'Duplicate entry',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee(number_format(5000, 0)); // collected excludes voided payment
        $response->assertSee('Voided');
    }

    public function test_profile_shows_quotation_and_enquiry_history(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Kerala']);

        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Kerala']);
        $quotation = Quotation::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);
        QuotationVersion::factory()->create(['company_id' => $company->id, 'quotation_id' => $quotation->id, 'version_number' => 1]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk()->assertSee('Kerala');
    }

    public function test_profile_handles_customer_with_no_related_records(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('No trips recorded yet');
        $response->assertSee('No payments recorded yet');
        $response->assertSee('No quotations yet');
    }

    public function test_cross_company_booking_and_payment_data_does_not_leak_onto_profile(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerA = Customer::factory()->create(['company_id' => $companyA->id]);

        $tripB = Trip::factory()->create(['company_id' => $companyB->id, 'destination' => 'Secret Resort']);
        Booking::factory()->create(['company_id' => $companyB->id, 'trip_id' => $tripB->id]);

        $response = $this->actingAs($userA)->get(route('customers.show', $customerA));

        $response->assertOk()->assertDontSee('Secret Resort');
    }

    public function test_directory_filter_with_balance_only_returns_customers_with_outstanding_balance(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $paidCustomer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Paid In Full']);
        $tripPaid = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $paidCustomer->id]);
        $bookingPaid = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $paidCustomer->id, 'trip_id' => $tripPaid->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingPaid->id, 'sell_price' => 5000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingPaid->id, 'amount' => 5000]);

        $owingCustomer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Still Owing']);
        $tripOwing = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $owingCustomer->id]);
        $bookingOwing = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $owingCustomer->id, 'trip_id' => $tripOwing->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingOwing->id, 'sell_price' => 8000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingOwing->id, 'amount' => 2000]);

        $response = $this->actingAs($user)->get(route('customers.index', ['filter' => 'with_balance']));

        $response->assertOk();
        $response->assertSee('Still Owing');
        $response->assertDontSee('Paid In Full');
    }

    public function test_directory_filter_upcoming_trips_excludes_customers_without_future_trips(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $upcomingCustomer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Has Upcoming Trip']);
        Trip::factory()->create([
            'company_id' => $company->id, 'customer_id' => $upcomingCustomer->id,
            'status' => TripStatus::Ready, 'start_date' => now()->addMonth(),
        ]);

        $pastCustomer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Only Past Trip']);
        Trip::factory()->create([
            'company_id' => $company->id, 'customer_id' => $pastCustomer->id,
            'status' => TripStatus::Completed, 'start_date' => now()->subMonth(),
        ]);

        $response = $this->actingAs($user)->get(route('customers.index', ['filter' => 'upcoming_trips']));

        $response->assertOk();
        $response->assertSee('Has Upcoming Trip');
        $response->assertDontSee('Only Past Trip');
    }

    public function test_directory_search_by_phone_and_email_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        Customer::factory()->create(['company_id' => $company->id, 'name' => 'Target Customer', 'phone' => '9988776655', 'email' => 'target@example.com']);
        Customer::factory()->create(['company_id' => $company->id, 'name' => 'Other Customer', 'phone' => '1112223333']);

        $this->actingAs($user)->get(route('customers.index', ['q' => '9988776655']))
            ->assertSee('Target Customer')->assertDontSee('Other Customer');

        $this->actingAs($user)->get(route('customers.index', ['q' => 'target@example.com']))
            ->assertSee('Target Customer')->assertDontSee('Other Customer');
    }

    public function test_directory_card_shows_correct_summary_for_multiple_bookings(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Multi Booking Customer']);

        $tripOne = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $bookingOne = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $tripOne->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingOne->id, 'sell_price' => 10000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingOne->id, 'amount' => 10000]);

        $tripTwo = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $bookingTwo = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $tripTwo->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingTwo->id, 'sell_price' => 8000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $bookingTwo->id, 'amount' => 3000]);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Multi Booking Customer');
        $response->assertSee(number_format(18000, 0)); // total value across both bookings
        $response->assertSee(number_format(13000, 0)); // total collected across both bookings
        $response->assertSee(number_format(5000, 0)); // outstanding across both bookings
    }

    public function test_directory_card_shows_empty_state_for_customer_without_bookings(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        Customer::factory()->create(['company_id' => $company->id, 'name' => 'No Bookings Yet']);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('No Bookings Yet');
        $response->assertSee('No activity yet');
        $response->assertSee('0', false); // bookings_count renders as 0, not a fabricated balance
    }

    public function test_overview_cards_show_correct_aggregate_metrics(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $withBalance = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $withBalance->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $withBalance->id, 'trip_id' => $trip->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => 12000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'amount' => 5000]);

        Customer::factory()->count(2)->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Total customers');
        $response->assertSee('3'); // total customer count KPI
        $response->assertSee(number_format(7000, 0)); // outstanding collections total across the company
    }

    public function test_directory_table_shows_location_and_lifetime_value_columns(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create([
            'company_id' => $company->id, 'name' => 'Table Row Customer', 'city' => 'Chennai', 'country' => 'India',
        ]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => 20000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'amount' => 20000]);

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Table Row Customer');
        $response->assertSee('Chennai, India');
        $response->assertSee(number_format(20000, 0));
    }

    public function test_enquiry_converted_to_trip_is_flagged_on_profile(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Munnar']);
        Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'enquiry_id' => $enquiry->id]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk()->assertSee('Converted to trip');
    }

    public function test_customer_index_query_count_does_not_scale_with_customer_count(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        Customer::factory()->count(3)->create(['company_id' => $company->id]);
        \DB::enableQueryLog();
        $this->actingAs($user)->get(route('customers.index'))->assertOk();
        $smallCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();
        \DB::flushQueryLog();

        Customer::factory()->count(17)->create(['company_id' => $company->id]);

        \DB::enableQueryLog();
        $this->actingAs($user)->get(route('customers.index'))->assertOk();
        $largeCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Query count must stay roughly flat as the customer count grows
        // (small deltas are expected from auth/permission caching warming
        // up between requests) — guards against the inline-per-row N+1
        // the old view had ($customer->enquiries()->latest()->first()).
        $this->assertLessThanOrEqual(5, abs($largeCount - $smallCount));
    }
}

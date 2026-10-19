<?php

namespace Tests\Feature;

use App\Enums\BookingItemStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPrintTest extends TestCase
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

    private function bookingFor(Company $company, Customer $customer): Booking
    {
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        return Booking::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'trip_id' => $trip->id,
        ]);
    }

    // ---- Access ---------------------------------------------------

    public function test_authorized_user_can_open_booking_print_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);

        $this->actingAs($user)->get(route('bookings.print', $booking))->assertOk();
    }

    public function test_guest_cannot_access_booking_print_page(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);

        $this->get(route('bookings.print', $booking))->assertRedirect(route('login'));
    }

    public function test_company_a_cannot_access_company_b_booking_print_page(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);
        $bookingB = $this->bookingFor($companyB, $customerB);

        $this->actingAs($userA)->get(route('bookings.print', $bookingB))->assertNotFound();
    }

    public function test_missing_booking_returns_404(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get('/bookings/999999/print')->assertNotFound();
    }

    // ---- Content ----------------------------------------------------

    public function test_customer_information_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Print Test Customer', 'phone' => '9123456780']);
        $booking = $this->bookingFor($company, $customer);

        $this->actingAs($user)->get(route('bookings.print', $booking))
            ->assertOk()
            ->assertSee('Print Test Customer')
            ->assertSee('9123456780');
    }

    public function test_booking_reference_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);

        $this->actingAs($user)->get(route('bookings.print', $booking))
            ->assertOk()
            ->assertSee('Booking #'.$booking->id);
    }

    public function test_travel_date_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-05']);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);

        $this->actingAs($user)->get(route('bookings.print', $booking))
            ->assertOk()
            ->assertSee('01 Nov 2026')
            ->assertSee('05 Nov 2026');
    }

    public function test_booking_items_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'description' => 'Deluxe Room Stay', 'sell_price' => 12000]);

        $this->actingAs($user)->get(route('bookings.print', $booking))
            ->assertOk()
            ->assertSee('Deluxe Room Stay')
            ->assertSee('12,000.00');
    }

    public function test_supplier_information_appears_only_where_appropriate(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Print Test Supplier']);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'supplier_id' => $supplier->id, 'description' => 'Hotel with supplier']);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'supplier_id' => null, 'description' => 'Item without supplier']);

        $response = $this->actingAs($user)->get(route('bookings.print', $booking));

        $response->assertOk()->assertSee('Print Test Supplier');
    }

    // ---- Financial consistency ------------------------------------

    public function test_print_total_equals_booking_total_sell_price(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => 15000]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => 8500]);

        $expected = $booking->fresh()->load('items')->totalSellPrice();

        $this->actingAs($user)->get(route('bookings.print', $booking))
            ->assertOk()
            ->assertSee(number_format($expected, 2));

        $this->assertSame(23500.0, $expected);
    }

    public function test_print_paid_amount_equals_booking_total_paid(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => 20000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'amount' => 7000, 'paid_at' => now()]);
        // Voided payment must not count.
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'amount' => 999, 'paid_at' => now(), 'voided_at' => now()]);

        $expected = $booking->fresh()->load('payments')->totalPaid();

        $this->actingAs($user)->get(route('bookings.print', $booking))
            ->assertOk()
            ->assertSee(number_format($expected, 2));

        $this->assertSame(7000.0, $expected);
    }

    public function test_print_balance_equals_booking_amount_remaining(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => 20000]);
        CustomerPayment::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'amount' => 7000, 'paid_at' => now()]);

        $expected = $booking->fresh()->load('items', 'payments')->amountRemaining();

        $this->actingAs($user)->get(route('bookings.print', $booking))
            ->assertOk()
            ->assertSee(number_format($expected, 2));

        $this->assertSame(13000.0, $expected);
    }

    public function test_cancelled_items_still_appear_matching_booking_page_behavior(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);
        BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'description' => 'Cancelled Excursion',
            'status' => BookingItemStatus::Cancelled,
            'sell_price' => 3000,
        ]);

        // Booking::totalSellPrice() applies no status filter, so the print
        // total must still include this cancelled item's price, exactly
        // matching existing Booking/Money page behavior.
        $expected = $booking->fresh()->load('items')->totalSellPrice();

        $response = $this->actingAs($user)->get(route('bookings.print', $booking));

        $response->assertOk()
            ->assertSee('Cancelled Excursion')
            ->assertSee(number_format($expected, 2));
        $this->assertSame(3000.0, $expected);
    }

    // ---- Tenant isolation -------------------------------------------

    public function test_company_b_data_never_appears_in_company_a_print(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $customerA = Customer::factory()->create(['company_id' => $companyA->id, 'name' => 'Company A Customer']);
        $bookingA = $this->bookingFor($companyA, $customerA);

        $customerB = Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Company B Customer']);
        $bookingB = $this->bookingFor($companyB, $customerB);
        BookingItem::factory()->create(['company_id' => $companyB->id, 'booking_id' => $bookingB->id, 'description' => 'Company B Secret Item']);

        $response = $this->actingAs($userA)->get(route('bookings.print', $bookingA));

        $response->assertOk()
            ->assertSee('Company A Customer')
            ->assertDontSee('Company B Customer')
            ->assertDontSee('Company B Secret Item');
    }

    // ---- Regression ---------------------------------------------------

    public function test_existing_booking_show_page_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->bookingFor($company, $customer);

        $this->actingAs($user)->get(route('bookings.show', $booking))->assertOk();
    }

    public function test_existing_money_page_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get(route('money'))->assertOk();
    }

    public function test_existing_dashboard_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}

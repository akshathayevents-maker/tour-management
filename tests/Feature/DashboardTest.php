<?php

namespace Tests\Feature;

use App\Enums\BookingItemStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Enquiry;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    private function tripFor(Company $company, ?Customer $customer = null): Trip
    {
        $customer ??= Customer::factory()->create(['company_id' => $company->id]);

        return Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
    }

    private function confirmedBooking(Company $company, ?Trip $trip = null): Booking
    {
        $trip ??= $this->tripFor($company);

        return Booking::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $trip->customer_id,
            'trip_id' => $trip->id,
            'status' => BookingStatus::Confirmed,
        ]);
    }

    public function test_dashboard_loads_successfully(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_existing_leads_and_enquiries_still_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $lead = Lead::factory()->create(['company_id' => $company->id, 'name' => 'Priya Sharma']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $enquiry = Enquiry::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Bali Escape']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Priya Sharma')
            ->assertSee('Bali Escape');
    }

    public function test_overdue_and_today_follow_ups_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = Lead::factory()->create(['company_id' => $company->id, 'name' => 'Overdue Lead Person']);

        FollowUp::factory()->create([
            'company_id' => $company->id,
            'followupable_type' => Lead::class,
            'followupable_id' => $lead->id,
            'due_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertSee('Overdue Lead Person');
    }

    public function test_bookings_needing_attention_shows_bookings_with_outstanding_balance(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Balance Due Customer']);
        $trip = $this->tripFor($company, $customer);
        $booking = $this->confirmedBooking($company, $trip);

        BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'sell_price' => 50000,
            'status' => BookingItemStatus::Confirmed,
        ]);

        CustomerPayment::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'amount' => 20000,
        ]);

        $this->assertSame(30000.0, $booking->fresh()->load('items', 'payments')->amountRemaining());

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Balance Due Customer');
    }

    public function test_fully_paid_bookings_do_not_appear_in_attention_list(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Fully Paid Customer']);
        $trip = $this->tripFor($company, $customer);
        $booking = $this->confirmedBooking($company, $trip);

        BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'sell_price' => 10000,
            'status' => BookingItemStatus::Confirmed,
        ]);

        CustomerPayment::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'amount' => 10000,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('bookingsNeedingAttention', fn ($bookings) => $bookings->isEmpty());
    }

    public function test_unconfirmed_supplier_items_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->confirmedBooking($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Pending Resort Supplier']);

        BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'supplier_id' => $supplier->id,
            'status' => BookingItemStatus::Requested,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pending Resort Supplier');
    }

    public function test_confirmed_supplier_items_do_not_appear_as_unconfirmed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->confirmedBooking($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Already Confirmed Supplier']);

        BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'supplier_id' => $supplier->id,
            'status' => BookingItemStatus::Confirmed,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Already Confirmed Supplier');
    }

    public function test_todays_customer_payments_appear_with_correct_total(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->confirmedBooking($company);

        CustomerPayment::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'amount' => 15000,
            'paid_at' => now()->toDateString(),
        ]);
        CustomerPayment::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'amount' => 5000,
            'paid_at' => now()->toDateString(),
        ]);
        // Yesterday's payment must not be counted in today's total.
        CustomerPayment::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'amount' => 99999,
            'paid_at' => now()->subDay()->toDateString(),
        ]);
        // A voided payment must not count either.
        CustomerPayment::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'amount' => 77777,
            'paid_at' => now()->toDateString(),
            'voided_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('todayCustomerPaymentTotal', 20000.0);
        $response->assertViewHas('todayCustomerPayments', fn ($payments) => $payments->count() === 2);
    }

    public function test_supplier_payments_needing_attention_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->confirmedBooking($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Payable Supplier Co']);

        $item = BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'supplier_id' => $supplier->id,
            'cost' => 8000,
            'status' => BookingItemStatus::Confirmed,
        ]);

        SupplierPayment::factory()->create([
            'company_id' => $company->id,
            'booking_item_id' => $item->id,
            'amount' => 3000,
        ]);

        $this->assertSame(5000.0, $item->fresh()->load('supplierPayments')->amountPayable());

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Payable Supplier Co');
    }

    public function test_fully_paid_supplier_items_do_not_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->confirmedBooking($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Settled Supplier Co']);

        $item = BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'supplier_id' => $supplier->id,
            'cost' => 4000,
            'status' => BookingItemStatus::Confirmed,
        ]);

        SupplierPayment::factory()->create([
            'company_id' => $company->id,
            'booking_item_id' => $item->id,
            'amount' => 4000,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Settled Supplier Co');
    }

    public function test_recent_bookings_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Recent Booking Customer']);
        $this->confirmedBooking($company, $this->tripFor($company, $customer));

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Recent Booking Customer');
    }

    public function test_dashboard_does_not_expose_other_company_data(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        // Company B booking with outstanding balance, unconfirmed supplier
        // item, today's payment and payable supplier item — each of these
        // must be invisible to Company A.
        $customerB = Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Other Company Customer']);
        $tripB = $this->tripFor($companyB, $customerB);
        $bookingB = $this->confirmedBooking($companyB, $tripB);

        BookingItem::factory()->create([
            'company_id' => $companyB->id,
            'booking_id' => $bookingB->id,
            'sell_price' => 20000,
            'status' => BookingItemStatus::Confirmed,
        ]);

        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id, 'name' => 'Other Company Supplier']);
        $itemB = BookingItem::factory()->create([
            'company_id' => $companyB->id,
            'booking_id' => $bookingB->id,
            'supplier_id' => $supplierB->id,
            'cost' => 5000,
            'status' => BookingItemStatus::Requested,
        ]);

        CustomerPayment::factory()->create([
            'company_id' => $companyB->id,
            'booking_id' => $bookingB->id,
            'amount' => 12345,
            'paid_at' => now()->toDateString(),
        ]);

        $response = $this->actingAs($userA)->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('Other Company Customer')
            ->assertDontSee('Other Company Supplier')
            ->assertDontSee('12345');

        // The financial total must also be scoped — Company A's total
        // must not include Company B's payment.
        $response->assertViewHas('todayCustomerPaymentTotal', 0.0);
    }
}

<?php

namespace Tests\Feature;

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

class CustomerPaymentReceiptPrintTest extends TestCase
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

    private function bookingWithPrice(Company $company, float $price = 50000, string $destination = 'Goa', string $customerName = 'Rahul Kumar'): Booking
    {
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => $customerName]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => $destination]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => $price]);

        return $booking->fresh(['items', 'payments']);
    }

    private function paymentFor(Booking $booking, array $overrides = []): CustomerPayment
    {
        return CustomerPayment::factory()->create(array_merge([
            'company_id' => $booking->company_id,
            'booking_id' => $booking->id,
            'amount' => 20000,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ], $overrides));
    }

    // ---- Access ---------------------------------------------------

    public function test_authorized_user_can_print_receipt(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking);

        $this->actingAs($user)->get(route('customer-payments.print', $payment))->assertOk();
    }

    public function test_guest_cannot_access_print_page(): void
    {
        $company = Company::factory()->create();
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking);

        $this->get(route('customer-payments.print', $payment))->assertRedirect(route('login'));
    }

    public function test_cross_company_payment_is_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithPrice($companyB);
        $paymentB = $this->paymentFor($bookingB);

        $this->actingAs($userA)->get(route('customer-payments.print', $paymentB))->assertNotFound();
    }

    public function test_nonexistent_payment_returns_404(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get('/customer-payments/999999/print')->assertNotFound();
    }

    // ---- Content ----------------------------------------------------

    public function test_customer_and_trip_information_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000, 'Kerala Backwaters', 'Priya Nair');
        $payment = $this->paymentFor($booking);

        $this->actingAs($user)->get(route('customer-payments.print', $payment))
            ->assertOk()
            ->assertSee('Priya Nair')
            ->assertSee('Kerala Backwaters');
    }

    public function test_company_information_appears(): void
    {
        $company = Company::factory()->create(['name' => 'Print Test Travels', 'email' => 'hello@print-test.example']);
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking);

        $this->actingAs($user)->get(route('customer-payments.print', $payment))
            ->assertOk()
            ->assertSee('Print Test Travels')
            ->assertSee('hello@print-test.example');
    }

    public function test_payment_date_amount_method_and_reference_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking, ['amount' => 25000, 'method' => 'upi', 'reference' => 'UPI-REF-9988']);

        $this->actingAs($user)->get(route('customer-payments.print', $payment))
            ->assertOk()
            ->assertSee($payment->paid_at->format('d M Y'))
            ->assertSee(number_format(25000, 2))
            ->assertSee('UPI')
            ->assertSee('UPI-REF-9988');
    }

    public function test_reference_section_absent_when_not_set(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking, ['reference' => null]);

        $this->actingAs($user)->get(route('customer-payments.print', $payment))
            ->assertOk()
            ->assertDontSee('Payment reference');
    }

    public function test_notes_appear_when_present(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking, ['notes' => 'Advance towards booking confirmation.']);

        $this->actingAs($user)->get(route('customer-payments.print', $payment))
            ->assertOk()
            ->assertSee('Advance towards booking confirmation.');
    }

    // ---- Financial consistency -----------------------------------------

    public function test_printed_amount_matches_persisted_payment_amount(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking, ['amount' => 17500.50]);

        $this->assertEquals(17500.50, $payment->fresh()->amount);

        $this->actingAs($user)->get(route('customer-payments.print', $payment))
            ->assertOk()
            ->assertSee(number_format(17500.50, 2));
    }

    // ---- Security -----------------------------------------------------

    public function test_internal_supplier_and_cost_information_never_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Secret Internal Supplier']);
        BookingItem::factory()->create([
            'company_id' => $company->id, 'booking_id' => $booking->id, 'supplier_id' => $supplier->id,
            'sell_price' => 50000, 'cost' => 24680,
        ]);
        $payment = $this->paymentFor($booking->fresh(['items']));

        $content = $this->actingAs($user)->get(route('customer-payments.print', $payment))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Secret Internal Supplier', $content);
        $this->assertStringNotContainsString('24,680.00', $content);
        $this->assertStringNotContainsString('24680', $content);
    }

    public function test_company_b_payment_data_never_appears_in_company_a_print(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $bookingA = $this->bookingWithPrice($companyA);
        $paymentA = $this->paymentFor($bookingA);

        $bookingB = $this->bookingWithPrice($companyB, 50000, 'Secret Destination', 'Secret Customer');
        $this->paymentFor($bookingB, ['reference' => 'SECRET-REF-1234']);

        $response = $this->actingAs($userA)->get(route('customer-payments.print', $paymentA));
        $response->assertOk()
            ->assertDontSee('Secret Destination')
            ->assertDontSee('Secret Customer')
            ->assertDontSee('SECRET-REF-1234');
    }

    // ---- Lifecycle (void) ------------------------------------------------

    public function test_voided_payment_is_still_printable_and_shows_voided_state(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking);
        $payment->void('Incorrect entry');

        $response = $this->actingAs($user)->get(route('customer-payments.print', $payment->fresh()));
        $response->assertOk()
            ->assertSee('This payment was voided.')
            ->assertSee('Incorrect entry');
    }

    public function test_void_semantics_unchanged(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking);

        $this->actingAs($user)->post(route('customer-payments.void', $payment), ['reason' => 'Test void'])
            ->assertRedirect();

        $this->assertTrue($payment->fresh()->isVoided());
        // Void is a status flag, never a delete — the row still exists.
        $this->assertNotNull(CustomerPayment::find($payment->id));
    }

    // ---- Performance ----------------------------------------------------

    public function test_no_n_plus_one_query_growth(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking);

        $this->actingAs($user);
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->get(route('customer-payments.print', $payment));
        $queryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertLessThan(20, $queryCount, "queryCount={$queryCount}");
    }

    // ---- Regression -----------------------------------------------------

    public function test_existing_receipt_page_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);
        $payment = $this->paymentFor($booking);

        $this->actingAs($user)->get(route('customer-payments.receipt', $payment))->assertOk();
    }

    public function test_money_dashboard_and_other_print_documents_still_work(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company);

        $this->actingAs($user)->get(route('money'))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('bookings.print', $booking))->assertOk();
    }
}

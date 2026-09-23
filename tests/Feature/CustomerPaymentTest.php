<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPaymentTest extends TestCase
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

    private function bookingWithPrice(Company $company, float $price = 50000): Booking
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'sell_price' => $price]);

        return $booking->fresh(['items', 'payments']);
    }

    public function test_create_payment(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $response = $this->actingAs($user)->post(route('customer-payments.store', $booking), [
            'amount' => 20000,
            'paid_at' => now()->toDateString(),
            'method' => 'upi',
        ]);

        $response->assertRedirect(route('bookings.show', $booking));
        $payment = CustomerPayment::first();
        $this->assertEquals(20000, $payment->amount);
        $this->assertTrue($payment->method === PaymentMethod::Upi);
        $this->assertSame($company->id, $payment->company_id);
        $this->assertSame($user->id, $payment->created_by);
    }

    public function test_partial_payment(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), [
            'amount' => 20000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);

        $booking->refresh()->load('items', 'payments');
        $this->assertEquals(20000, $booking->totalPaid());
        $this->assertEquals(30000, $booking->amountRemaining());
    }

    public function test_full_payment(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), [
            'amount' => 50000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);

        $booking->refresh()->load('items', 'payments');
        $this->assertEquals(0, $booking->amountRemaining());
        $this->assertTrue($booking->isFullyPaid());
    }

    public function test_multiple_payments(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 60000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), ['amount' => 10000, 'paid_at' => now()->toDateString(), 'method' => 'upi']);
        $this->actingAs($user)->post(route('customer-payments.store', $booking->fresh(['items', 'payments'])), ['amount' => 20000, 'paid_at' => now()->toDateString(), 'method' => 'bank_transfer']);
        $this->actingAs($user)->post(route('customer-payments.store', $booking->fresh(['items', 'payments'])), ['amount' => 30000, 'paid_at' => now()->toDateString(), 'method' => 'cash']);

        $this->assertSame(3, CustomerPayment::count());
        $booking->refresh()->load('items', 'payments');
        $this->assertEquals(60000, $booking->totalPaid());
        $this->assertEquals(0, $booking->amountRemaining());
    }

    public function test_remaining_balance_calculation_is_correct(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 45000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), ['amount' => 12345.67, 'paid_at' => now()->toDateString(), 'method' => 'cash']);

        $booking->refresh()->load('items', 'payments');
        $this->assertEqualsWithDelta(32654.33, $booking->amountRemaining(), 0.01);
    }

    public function test_zero_amount_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), [
            'amount' => 0, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, CustomerPayment::count());
    }

    public function test_negative_amount_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), [
            'amount' => -100, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, CustomerPayment::count());
    }

    public function test_overpayment_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), [
            'amount' => 60000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, CustomerPayment::count());
    }

    public function test_overpayment_rejected_across_multiple_payments(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), ['amount' => 40000, 'paid_at' => now()->toDateString(), 'method' => 'cash']);

        $this->actingAs($user)->post(route('customer-payments.store', $booking->fresh(['items', 'payments'])), [
            'amount' => 15000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(1, CustomerPayment::count());
    }

    public function test_invalid_payment_method_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), [
            'amount' => 1000, 'paid_at' => now()->toDateString(), 'method' => 'bitcoin',
        ])->assertSessionHasErrors('method');

        $this->assertSame(0, CustomerPayment::count());
    }

    public function test_missing_required_fields_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), [])
            ->assertSessionHasErrors(['amount', 'paid_at', 'method']);
    }

    public function test_reference_and_notes_are_optional(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);

        $this->actingAs($user)->post(route('customer-payments.store', $booking), [
            'amount' => 1000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertSessionDoesntHaveErrors();

        $payment = CustomerPayment::first();
        $this->assertNull($payment->reference);
        $this->assertNull($payment->notes);
    }

    public function test_payment_can_be_voided_and_excluded_from_totals(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);
        $this->actingAs($user)->post(route('customer-payments.store', $booking), ['amount' => 20000, 'paid_at' => now()->toDateString(), 'method' => 'cash']);
        $payment = CustomerPayment::first();

        $this->actingAs($user)->post(route('customer-payments.void', $payment), ['reason' => 'Entered by mistake'])
            ->assertRedirect();

        $payment->refresh();
        $this->assertTrue($payment->isVoided());
        $this->assertSame('Entered by mistake', $payment->voided_reason);

        $booking->refresh()->load('items', 'payments');
        $this->assertEquals(0, $booking->totalPaid());
        $this->assertEquals(50000, $booking->amountRemaining());
    }

    public function test_voiding_requires_a_reason(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);
        $this->actingAs($user)->post(route('customer-payments.store', $booking), ['amount' => 20000, 'paid_at' => now()->toDateString(), 'method' => 'cash']);
        $payment = CustomerPayment::first();

        $this->actingAs($user)->post(route('customer-payments.void', $payment), [])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($payment->fresh()->isVoided());
    }

    public function test_a_voided_payment_frees_up_room_for_a_replacement_payment(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);
        $this->actingAs($user)->post(route('customer-payments.store', $booking), ['amount' => 50000, 'paid_at' => now()->toDateString(), 'method' => 'cash']);
        $payment = CustomerPayment::first();
        $this->actingAs($user)->post(route('customer-payments.void', $payment), ['reason' => 'Wrong amount']);

        $this->actingAs($user)->post(route('customer-payments.store', $booking->fresh(['items', 'payments'])), [
            'amount' => 50000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(2, CustomerPayment::count());
    }

    public function test_payment_allowed_against_completed_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);
        $this->actingAs($user)->post(route('bookings.complete', $booking));

        $this->actingAs($user)->post(route('customer-payments.store', $booking->fresh(['items', 'payments'])), [
            'amount' => 20000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, CustomerPayment::count());
    }

    public function test_payment_allowed_against_cancelled_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);
        $this->actingAs($user)->post(route('bookings.cancel', $booking));

        $this->actingAs($user)->post(route('customer-payments.store', $booking->fresh(['items', 'payments'])), [
            'amount' => 20000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, CustomerPayment::count());
    }

    public function test_cross_company_payment_creation_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithPrice($companyB, 50000);

        $this->actingAs($userA)->post(route('customer-payments.store', $bookingB), [
            'amount' => 1000, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ])->assertNotFound();

        $this->assertSame(0, CustomerPayment::count());
    }

    public function test_cross_company_void_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithPrice($companyB, 50000);
        $paymentB = CustomerPayment::factory()->create(['company_id' => $companyB->id, 'booking_id' => $bookingB->id]);

        $this->actingAs($userA)->post(route('customer-payments.void', $paymentB), ['reason' => 'Hacked'])
            ->assertNotFound();

        $this->assertFalse($paymentB->fresh()->isVoided());
    }

    public function test_cross_company_receipt_access_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithPrice($companyB, 50000);
        $paymentB = CustomerPayment::factory()->create(['company_id' => $companyB->id, 'booking_id' => $bookingB->id]);

        $this->actingAs($userA)->get(route('customer-payments.receipt', $paymentB))->assertNotFound();
    }

    public function test_payment_against_inaccessible_booking_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithPrice($companyB, 50000);

        $this->actingAs($userA)->get(route('bookings.show', $bookingB))->assertNotFound();
    }

    public function test_payment_cannot_be_voided_twice(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithPrice($company, 50000);
        $this->actingAs($user)->post(route('customer-payments.store', $booking), ['amount' => 20000, 'paid_at' => now()->toDateString(), 'method' => 'cash']);
        $payment = CustomerPayment::first();
        $this->actingAs($user)->post(route('customer-payments.void', $payment), ['reason' => 'First void']);

        $this->actingAs($user)->post(route('customer-payments.void', $payment->fresh()), ['reason' => 'Second void'])
            ->assertStatus(422);

        $this->assertSame('First void', $payment->fresh()->voided_reason);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPaymentTest extends TestCase
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

    private function bookingItemWithCost(Company $company, ?float $cost = 30000, ?Supplier $supplier = null): BookingItem
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);

        return BookingItem::factory()->create([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'description' => 'Hotel',
            'cost' => $cost,
            'supplier_id' => $supplier?->id,
        ]);
    }

    // --- Core ---

    public function test_supplier_cost_lives_on_booking_item(): void
    {
        $company = Company::factory()->create();
        $item = $this->bookingItemWithCost($company, 25000);

        $this->assertEquals(25000, $item->cost);
    }

    public function test_create_supplier_payment(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 25000);

        $response = $this->actingAs($user)->post(route('supplier-payments.store', $item), [
            'amount' => 25000, 'paid_at' => now()->toDateString(), 'method' => 'bank_transfer',
        ]);

        $response->assertRedirect(route('bookings.show', $item->booking));
        $payment = SupplierPayment::first();
        $this->assertEquals(25000, $payment->amount);
        $this->assertTrue($payment->method === PaymentMethod::BankTransfer);
        $this->assertSame($company->id, $payment->company_id);
        $this->assertSame($user->id, $payment->created_by);
    }

    public function test_correct_cost_paid_due_calculation(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 25000);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), [
            'amount' => 15000, 'paid_at' => now()->toDateString(),
        ]);

        $item->refresh()->load('supplierPayments');
        $this->assertEquals(25000, $item->cost);
        $this->assertEquals(15000, $item->totalPaidToSupplier());
        $this->assertEquals(10000, $item->amountPayable());
    }

    // --- Multiple payments ---

    public function test_partial_payment(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 30000);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 10000, 'paid_at' => now()->toDateString()]);

        $item->refresh()->load('supplierPayments');
        $this->assertEquals(20000, $item->amountPayable());
    }

    public function test_multiple_payments(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 30000);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 10000, 'paid_at' => now()->toDateString()]);
        $this->actingAs($user)->post(route('supplier-payments.store', $item->fresh()), ['amount' => 15000, 'paid_at' => now()->toDateString()]);

        $item->refresh()->load('supplierPayments');
        $this->assertSame(2, SupplierPayment::count());
        $this->assertEquals(25000, $item->totalPaidToSupplier());
        $this->assertEquals(5000, $item->amountPayable());
    }

    public function test_fully_paid(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 30000);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 30000, 'paid_at' => now()->toDateString()]);

        $item->refresh()->load('supplierPayments');
        $this->assertEquals(0, $item->amountPayable());
        $this->assertTrue($item->isFullyPaidToSupplier());
    }

    // --- Validation ---

    public function test_zero_amount_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), [
            'amount' => 0, 'paid_at' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, SupplierPayment::count());
    }

    public function test_negative_amount_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), [
            'amount' => -500, 'paid_at' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, SupplierPayment::count());
    }

    public function test_invalid_method_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), [
            'amount' => 1000, 'paid_at' => now()->toDateString(), 'method' => 'bitcoin',
        ])->assertSessionHasErrors('method');

        $this->assertSame(0, SupplierPayment::count());
    }

    public function test_method_is_optional(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), [
            'amount' => 1000, 'paid_at' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertNull(SupplierPayment::first()->method);
    }

    public function test_overpayment_beyond_cost_is_allowed(): void
    {
        // Deliberately NOT rejected — see architecture review Part 7/12:
        // cost is our own estimate, not a firm contract like the
        // customer's agreed amount, and an advance may legitimately be
        // paid before the final cost is even confirmed (Scenario F).
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), [
            'amount' => 25000, 'paid_at' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $item->refresh()->load('supplierPayments');
        $this->assertEquals(-5000, $item->amountPayable());
    }

    public function test_payment_allowed_before_cost_is_set(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, null);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), [
            'amount' => 10000, 'paid_at' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $item->refresh()->load('supplierPayments');
        $this->assertEquals(10000, $item->totalPaidToSupplier());
        $this->assertNull($item->amountPayable());
    }

    // --- Financial integrity ---

    public function test_payment_can_be_voided_and_excluded_from_totals(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 20000, 'paid_at' => now()->toDateString()]);
        $payment = SupplierPayment::first();

        $this->actingAs($user)->post(route('supplier-payments.void', $payment), ['reason' => 'Entered by mistake'])
            ->assertRedirect();

        $payment->refresh();
        $this->assertTrue($payment->isVoided());
        $this->assertSame('Entered by mistake', $payment->voided_reason);

        $item->refresh()->load('supplierPayments');
        $this->assertEquals(0, $item->totalPaidToSupplier());
        $this->assertEquals(20000, $item->amountPayable());
    }

    public function test_voiding_requires_a_reason(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 20000, 'paid_at' => now()->toDateString()]);
        $payment = SupplierPayment::first();

        $this->actingAs($user)->post(route('supplier-payments.void', $payment), [])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($payment->fresh()->isVoided());
    }

    public function test_cost_can_be_revised_and_history_survives(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 20000, 'paid_at' => now()->toDateString()]);

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => $item->description, 'cost' => 22000,
        ]);

        $item->refresh()->load('supplierPayments');
        $this->assertEquals(22000, $item->cost);
        // The original payment record is untouched.
        $this->assertEquals(20000, $item->supplierPayments->first()->amount);
        $this->assertEquals(2000, $item->amountPayable());
    }

    public function test_supplier_payment_snapshots_identity_independent_of_later_supplier_change(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $supplierA = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Supplier A', 'phone' => '1111111111']);
        $supplierB = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Supplier B', 'phone' => '2222222222']);
        $item = $this->bookingItemWithCost($company, 20000, $supplierA);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 20000, 'paid_at' => now()->toDateString()]);
        $payment = SupplierPayment::first();
        $this->assertSame('Supplier A', $payment->supplier_name_snapshot);

        // Operator later switches the booking item to a different supplier.
        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => $item->description, 'supplier_id' => $supplierB->id,
        ]);

        // The historical payment must still show who the money actually went to.
        $this->assertSame('Supplier A', $payment->fresh()->supplier_name_snapshot);
    }

    // --- Lifecycle ---

    public function test_payment_allowed_against_completed_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('bookings.complete', $item->booking));

        $this->actingAs($user)->post(route('supplier-payments.store', $item->fresh()), [
            'amount' => 20000, 'paid_at' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, SupplierPayment::count());
    }

    public function test_payment_allowed_against_cancelled_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('bookings.cancel', $item->booking));

        $this->actingAs($user)->post(route('supplier-payments.store', $item->fresh()), [
            'amount' => 10000, 'paid_at' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, SupplierPayment::count());
    }

    public function test_cost_cannot_be_changed_after_booking_completion(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('bookings.complete', $item->booking));

        $this->actingAs($user)->patch(route('booking-items.update', $item->fresh()), [
            'description' => $item->description, 'cost' => 99999,
        ])->assertStatus(422);

        $this->assertEquals(20000, $item->fresh()->cost);
    }

    // --- Tenant security ---

    public function test_cross_company_supplier_payment_creation_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $itemB = $this->bookingItemWithCost($companyB, 20000);

        $this->actingAs($userA)->post(route('supplier-payments.store', $itemB), [
            'amount' => 1000, 'paid_at' => now()->toDateString(),
        ])->assertNotFound();

        $this->assertSame(0, SupplierPayment::count());
    }

    public function test_cross_company_supplier_payment_void_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $itemB = $this->bookingItemWithCost($companyB, 20000);
        $paymentB = SupplierPayment::factory()->create(['company_id' => $companyB->id, 'booking_item_id' => $itemB->id]);

        $this->actingAs($userA)->post(route('supplier-payments.void', $paymentB), ['reason' => 'Hacked'])
            ->assertNotFound();

        $this->assertFalse($paymentB->fresh()->isVoided());
    }

    public function test_cross_company_booking_item_and_supplier_inaccessible(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $itemB = $this->bookingItemWithCost($companyB, 20000);
        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id]);

        $bookingId = $itemB->booking_id;

        $this->actingAs($userA)->get(route('bookings.show', $bookingId))->assertNotFound();
        $this->actingAs($userA)->get(route('suppliers.show', $supplierB->id))->assertNotFound();
    }

    public function test_supplier_payment_history_scoped_to_company_on_booking_page(): void
    {
        $companyA = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $itemA = $this->bookingItemWithCost($companyA, 20000);
        SupplierPayment::factory()->create(['company_id' => $companyA->id, 'booking_item_id' => $itemA->id, 'amount' => 5000]);

        $companyB = Company::factory()->create();
        $itemB = $this->bookingItemWithCost($companyB, 30000);
        SupplierPayment::factory()->create(['company_id' => $companyB->id, 'booking_item_id' => $itemB->id, 'amount' => 9999]);

        $this->actingAs($userA)->get(route('bookings.show', $itemA->booking))
            ->assertSee('5,000.00')
            ->assertDontSee('9,999.00');
    }

    // --- Final review regression tests ---

    public function test_payment_cannot_be_voided_twice(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 8000, 'paid_at' => now()->toDateString()]);
        $payment = SupplierPayment::first();
        $this->actingAs($user)->post(route('supplier-payments.void', $payment), ['reason' => 'First void']);

        $this->actingAs($user)->post(route('supplier-payments.void', $payment->fresh()), ['reason' => 'Second void'])
            ->assertStatus(422);

        // The original void reason must survive, not be overwritten by the second attempt.
        $this->assertSame('First void', $payment->fresh()->voided_reason);
    }

    public function test_partial_void_leaves_correct_remaining_balance(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 8000, 'paid_at' => now()->toDateString()]);
        $this->actingAs($user)->post(route('supplier-payments.store', $item->fresh()), ['amount' => 5000, 'paid_at' => now()->toDateString()]);
        $firstPayment = SupplierPayment::orderBy('id')->first();

        $this->actingAs($user)->post(route('supplier-payments.void', $firstPayment), ['reason' => 'Wrong item']);

        $item->refresh()->load('supplierPayments');
        $this->assertEquals(5000, $item->totalPaidToSupplier());
        $this->assertEquals(15000, $item->amountPayable());
    }

    public function test_overpaid_state_is_labelled_as_overpaid_not_negative_due(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 25000, 'paid_at' => now()->toDateString()]);

        $response = $this->actingAs($user)->get(route('bookings.show', $item->fresh()->booking));

        $response->assertSee('Overpaid by');
        $response->assertSee('5,000.00');
        $response->assertDontSee('Due -5,000.00');
    }

    public function test_cost_revised_below_paid_amount_shows_overpaid_not_negative_due(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 8000, 'paid_at' => now()->toDateString()]);

        // Cost first revised up (₹25,000), then down below what's already paid (₹7,000).
        $this->actingAs($user)->patch(route('booking-items.update', $item->fresh()), ['description' => $item->description, 'cost' => 25000]);
        $item->refresh()->load('supplierPayments');
        $this->assertEquals(17000, $item->amountPayable());

        $this->actingAs($user)->patch(route('booking-items.update', $item->fresh()), ['description' => $item->description, 'cost' => 7000]);
        $item->refresh()->load('supplierPayments');
        $this->assertEquals(-1000, $item->amountPayable());
        // The original ₹8,000 payment is untouched by the cost revision.
        $this->assertEquals(8000, $item->totalPaidToSupplier());

        $response = $this->actingAs($user)->get(route('bookings.show', $item->booking));
        $response->assertSee('Overpaid by');
        $response->assertDontSee('Due -1,000.00');
    }

    public function test_null_cost_with_payment_shows_paid_amount_not_hidden(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, null);

        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 10000, 'paid_at' => now()->toDateString()]);

        $response = $this->actingAs($user)->get(route('bookings.show', $item->fresh()->booking));

        $response->assertSee('Cost not confirmed yet');
        $response->assertSee('10,000.00');
    }

    public function test_payment_history_shows_supplier_snapshot(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $supplierA = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'ABC Resort']);
        $item = $this->bookingItemWithCost($company, 20000, $supplierA);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 10000, 'paid_at' => now()->toDateString()]);

        $response = $this->actingAs($user)->get(route('bookings.show', $item->fresh()->booking));

        $response->assertSee('ABC Resort');
    }

    public function test_payment_history_still_shows_original_supplier_after_supplier_changed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $supplierA = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'ABC Resort']);
        $supplierB = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'XYZ Resort']);
        $item = $this->bookingItemWithCost($company, 20000, $supplierA);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 10000, 'paid_at' => now()->toDateString()]);

        $this->actingAs($user)->patch(route('booking-items.update', $item->fresh()), [
            'description' => $item->description, 'supplier_id' => $supplierB->id,
        ]);

        $response = $this->actingAs($user)->get(route('bookings.show', $item->fresh()->booking));

        // The payment history line must still say ABC Resort — the money
        // actually went there — even though the item's current supplier
        // is now XYZ Resort.
        $response->assertSee('paid to ABC Resort');
    }

    public function test_void_allowed_after_booking_completed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $item = $this->bookingItemWithCost($company, 20000);
        $this->actingAs($user)->post(route('supplier-payments.store', $item), ['amount' => 8000, 'paid_at' => now()->toDateString()]);
        $payment = SupplierPayment::first();
        $this->actingAs($user)->post(route('bookings.complete', $item->fresh()->booking));

        $this->actingAs($user)->post(route('supplier-payments.void', $payment), ['reason' => 'Late correction'])
            ->assertRedirect();

        $this->assertTrue($payment->fresh()->isVoided());
    }
}

<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    private function bookingWithItems(Company $company): Booking
    {
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Rahul Kumar']);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'description' => 'Hotel', 'sell_price' => 30000]);
        BookingItem::factory()->create(['company_id' => $company->id, 'booking_id' => $booking->id, 'description' => 'Transport', 'sell_price' => 10000]);

        return $booking->fresh(['items']);
    }

    public function test_create_invoice(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);

        $response = $this->actingAs($user)->post(route('invoices.store', $booking));

        $invoice = Invoice::first();
        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame($company->id, $invoice->company_id);
        $this->assertTrue($invoice->status === InvoiceStatus::Issued);
        $this->assertSame('Rahul Kumar', $invoice->customer_name_snapshot);
    }

    public function test_invoice_amount_is_correct(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);

        $this->actingAs($user)->post(route('invoices.store', $booking));

        $invoice = Invoice::first();
        $this->assertEquals(40000, $invoice->subtotal);
        $this->assertEquals(40000, $invoice->total);
        $this->assertNull($invoice->tax_amount);
        $this->assertSame(2, $invoice->lineItems->count());
    }

    public function test_invoice_numbering_is_sequential_per_company(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking1 = $this->bookingWithItems($company);
        $booking2 = $this->bookingWithItems($company);

        $this->actingAs($user)->post(route('invoices.store', $booking1));
        $this->actingAs($user)->post(route('invoices.store', $booking2));

        $invoices = Invoice::orderBy('id')->pluck('invoice_number');
        $this->assertSame(['INV-0001', 'INV-0002'], $invoices->toArray());
    }

    public function test_cannot_invoice_a_booking_with_no_priced_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);

        $this->actingAs($user)->post(route('invoices.store', $booking))
            ->assertSessionHasErrors('invoice');

        $this->assertSame(0, Invoice::count());
    }

    public function test_optional_tax_rate_is_applied_when_provided(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);

        $this->actingAs($user)->post(route('invoices.store', $booking), ['tax_rate' => 5]);

        $invoice = Invoice::first();
        $this->assertEquals(40000, $invoice->subtotal);
        $this->assertEquals(2000, $invoice->tax_amount);
        $this->assertEquals(42000, $invoice->total);
    }

    public function test_tax_rate_is_optional(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);

        $this->actingAs($user)->post(route('invoices.store', $booking))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Invoice::first()->tax_rate);
    }

    public function test_invoice_history_remains_stable_after_booking_items_change(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $this->actingAs($user)->post(route('invoices.store', $booking));
        $invoice = Invoice::first();

        // Booking price changes after the invoice was issued.
        $booking->items()->first()->update(['sell_price' => 99999]);

        $this->assertEquals(40000, $invoice->fresh()->total);
    }

    public function test_issued_invoice_can_be_cancelled_but_not_mutated(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $this->actingAs($user)->post(route('invoices.store', $booking));
        $invoice = Invoice::first();
        $originalTotal = $invoice->total;

        $this->actingAs($user)->post(route('invoices.cancel', $invoice), ['cancellation_reason' => 'Booking amount changed'])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertTrue($invoice->status === InvoiceStatus::Cancelled);
        $this->assertNotNull($invoice->cancelled_at);
        $this->assertEquals($originalTotal, $invoice->total);
    }

    public function test_already_cancelled_invoice_cannot_be_cancelled_again(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $this->actingAs($user)->post(route('invoices.store', $booking));
        $invoice = Invoice::first();
        $this->actingAs($user)->post(route('invoices.cancel', $invoice));

        $this->actingAs($user)->post(route('invoices.cancel', $invoice->fresh()))
            ->assertStatus(422);
    }

    public function test_multiple_invoices_can_exist_for_one_booking(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);

        $this->actingAs($user)->post(route('invoices.store', $booking));
        $this->actingAs($user)->post(route('invoices.store', $booking->fresh(['items'])));

        $this->assertSame(2, Invoice::where('booking_id', $booking->id)->count());
    }

    public function test_cross_company_invoice_access_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithItems($companyB);
        $this->actingAs($this->companyUser($companyB))->post(route('invoices.store', $bookingB));
        $invoiceB = Invoice::first();

        $this->actingAs($userA)->get(route('invoices.show', $invoiceB))->assertNotFound();
    }

    public function test_cross_company_invoice_creation_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithItems($companyB);

        $this->actingAs($userA)->post(route('invoices.store', $bookingB))->assertNotFound();

        $this->assertSame(0, Invoice::count());
    }

    public function test_cross_company_invoice_cancel_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithItems($companyB);
        $this->actingAs($this->companyUser($companyB))->post(route('invoices.store', $bookingB));
        $invoiceB = Invoice::first();

        $this->actingAs($userA)->post(route('invoices.cancel', $invoiceB))->assertNotFound();

        $this->assertTrue($invoiceB->fresh()->status === InvoiceStatus::Issued);
    }
}

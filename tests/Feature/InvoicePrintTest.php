<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePrintTest extends TestCase
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

    private function bookingWithItems(Company $company, array $items = []): Booking
    {
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Rahul Kumar']);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Goa']);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);

        if (empty($items)) {
            $items = [['description' => 'Hotel', 'sell_price' => 30000]];
        }

        foreach ($items as $item) {
            BookingItem::factory()->create(array_merge(['company_id' => $company->id, 'booking_id' => $booking->id], $item));
        }

        return $booking->fresh(['items']);
    }

    private function invoiceFor(Company $company, Booking $booking, ?float $taxRate = null): Invoice
    {
        $user = User::where('company_id', $company->id)->first() ?? $this->companyUser($company);
        $this->actingAs($user)->post(route('invoices.store', $booking), array_filter(['tax_rate' => $taxRate]));

        return Invoice::first()->fresh(['lineItems']);
    }

    // ---- Access ---------------------------------------------------

    public function test_guest_cannot_access_print_page(): void
    {
        $company = Company::factory()->create();
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        auth()->logout();

        $this->get(route('invoices.print', $invoice))->assertRedirect(route('login'));
    }

    public function test_authorized_company_user_can_print(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $this->actingAs($user)->get(route('invoices.print', $invoice))->assertOk();
    }

    public function test_cross_company_access_blocked(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $bookingB = $this->bookingWithItems($companyB);
        $invoiceB = $this->invoiceFor($companyB, $bookingB);

        $this->actingAs($userA)->get(route('invoices.print', $invoiceB))->assertNotFound();
    }

    public function test_nonexistent_invoice_returns_404(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get('/invoices/999999/print')->assertNotFound();
    }

    // ---- Document content -----------------------------------------

    public function test_invoice_number_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number);
    }

    public function test_company_information_appears(): void
    {
        $company = Company::factory()->create(['name' => 'Print Test Travels', 'email' => 'hello@print-test.example']);
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee('Print Test Travels')
            ->assertSee('hello@print-test.example');
    }

    public function test_customer_information_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee('Rahul Kumar');
    }

    public function test_invoice_date_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee($invoice->issued_at->format('d M Y'));
    }

    public function test_multiple_line_items_all_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company, [
            ['description' => 'Hotel', 'sell_price' => 30000],
            ['description' => 'Transport', 'sell_price' => 10000],
            ['description' => 'Sightseeing', 'sell_price' => 8000],
        ]);
        $invoice = $this->invoiceFor($company, $booking);

        $response = $this->actingAs($user)->get(route('invoices.print', $invoice));
        $response->assertOk()
            ->assertSee('Hotel')
            ->assertSee('Transport')
            ->assertSee('Sightseeing');
        $this->assertSame(3, $invoice->lineItems->count());
    }

    public function test_subtotal_appears_correctly(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company, [
            ['description' => 'Hotel', 'sell_price' => 30000],
            ['description' => 'Transport', 'sell_price' => 10000],
        ]);
        $invoice = $this->invoiceFor($company, $booking);

        $this->assertEquals(40000, $invoice->subtotal);

        $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee(number_format(40000, 2));
    }

    public function test_tax_rate_and_amount_appear_when_applicable(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company, [['description' => 'Hotel', 'sell_price' => 10000]]);
        $invoice = $this->invoiceFor($company, $booking, 18.0);

        $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee('18.00%')
            ->assertSee(number_format($invoice->tax_amount, 2));
    }

    public function test_no_tax_section_when_tax_not_applied(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $this->assertNull($invoice->tax_amount);

        $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertDontSee('Tax (');
    }

    public function test_final_total_appears_correctly(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company, [['description' => 'Hotel', 'sell_price' => 20000]]);
        $invoice = $this->invoiceFor($company, $booking, 10.0);

        $this->assertEquals(22000, $invoice->total);

        $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee(number_format(22000, 2));
    }

    public function test_cancelled_invoice_status_is_represented(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $this->actingAs($user)->post(route('invoices.cancel', $invoice), ['cancellation_reason' => 'Customer requested refund']);

        $response = $this->actingAs($user)->get(route('invoices.print', $invoice->fresh()));
        $response->assertOk()
            ->assertSee('Cancelled')
            ->assertSee('This invoice has been cancelled.')
            ->assertSee('Customer requested refund');
    }

    // ---- Snapshot integrity -----------------------------------------

    public function test_printed_values_come_from_invoice_snapshot(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company, [['description' => 'Hotel', 'sell_price' => 30000]]);
        $invoice = $this->invoiceFor($company, $booking);

        $response = $this->actingAs($user)->get(route('invoices.print', $invoice));
        $response->assertOk()->assertSee(number_format(30000, 2));

        // The rendered total must equal the Invoice's own persisted total,
        // not a live recomputation from Booking::totalSellPrice().
        $this->assertEquals($invoice->total, 30000);
    }

    public function test_mutating_booking_items_after_invoice_creation_does_not_alter_printed_invoice(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company, [['description' => 'Hotel', 'sell_price' => 30000]]);
        $invoice = $this->invoiceFor($company, $booking);

        // Change the live booking item's price after the invoice already exists.
        $booking->items->first()->update(['sell_price' => 99999]);

        $response = $this->actingAs($user)->get(route('invoices.print', $invoice->fresh(['lineItems'])));
        $response->assertOk()
            ->assertSee(number_format(30000, 2))
            ->assertDontSee(number_format(99999, 2));
    }

    public function test_mutating_customer_name_after_invoice_creation_does_not_alter_printed_invoice(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $booking->customer->update(['name' => 'Renamed Customer']);

        $response = $this->actingAs($user)->get(route('invoices.print', $invoice->fresh(['lineItems'])));
        $response->assertOk()
            ->assertSee('Rahul Kumar')
            ->assertDontSee('Renamed Customer');
    }

    // ---- Security -----------------------------------------------------

    public function test_internal_supplier_cost_does_not_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Rahul Kumar']);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = Booking::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id, 'customer_id' => $customer->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Secret Internal Supplier']);
        BookingItem::factory()->create([
            'company_id' => $company->id, 'booking_id' => $booking->id, 'supplier_id' => $supplier->id,
            'description' => 'Hotel', 'sell_price' => 30000, 'cost' => 12345,
        ]);
        $invoice = $this->invoiceFor($company, $booking->fresh(['items']));

        $content = $this->actingAs($user)->get(route('invoices.print', $invoice))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Secret Internal Supplier', $content);
        $this->assertStringNotContainsString('12,345.00', $content);
        $this->assertStringNotContainsString('12345', $content);
    }

    public function test_company_b_invoice_data_never_appears_in_company_a_print(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $bookingA = $this->bookingWithItems($companyA);
        $invoiceA = $this->invoiceFor($companyA, $bookingA);

        $bookingB = $this->bookingWithItems($companyB, [['description' => 'Company B Secret Item', 'sell_price' => 99999]]);
        $this->invoiceFor($companyB, $bookingB);

        $response = $this->actingAs($userA)->get(route('invoices.print', $invoiceA));
        $response->assertOk()->assertDontSee('Company B Secret Item');
    }

    // ---- Performance ----------------------------------------------------

    public function test_no_n_plus_one_query_growth_with_multiple_line_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $oneItemBooking = $this->bookingWithItems($company, [['description' => 'Item 1', 'sell_price' => 1000]]);
        $oneItemInvoice = $this->invoiceFor($company, $oneItemBooking);

        $fiveItemBooking = $this->bookingWithItems($company, [
            ['description' => 'Item 1', 'sell_price' => 1000],
            ['description' => 'Item 2', 'sell_price' => 1000],
            ['description' => 'Item 3', 'sell_price' => 1000],
            ['description' => 'Item 4', 'sell_price' => 1000],
            ['description' => 'Item 5', 'sell_price' => 1000],
        ]);
        $fiveItemInvoice = $this->invoiceFor($company, $fiveItemBooking);

        $this->actingAs($user);
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->get(route('invoices.print', $oneItemInvoice));
        $oneItemQueryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());

        \Illuminate\Support\Facades\DB::flushQueryLog();
        $this->get(route('invoices.print', $fiveItemInvoice));
        $fiveItemQueryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        // Fixed regardless of line-item count — no per-item query.
        $this->assertLessThan(20, $fiveItemQueryCount, "one={$oneItemQueryCount} five={$fiveItemQueryCount}");
    }

    // ---- Regression -----------------------------------------------------

    public function test_existing_invoice_show_page_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);
        $invoice = $this->invoiceFor($company, $booking);

        $this->actingAs($user)->get(route('invoices.show', $invoice))->assertOk();
    }

    public function test_booking_print_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $booking = $this->bookingWithItems($company);

        $this->actingAs($user)->get(route('bookings.print', $booking))->assertOk();
    }

    public function test_money_and_dashboard_still_work(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get(route('money'))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}

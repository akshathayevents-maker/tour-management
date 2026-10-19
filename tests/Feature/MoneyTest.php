<?php

namespace Tests\Feature;

use App\Enums\BookingItemStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyTest extends TestCase
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

    private function tripFor(Company $company, Customer $customer): Trip
    {
        return Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
    }

    private function booking(Company $company, Customer $customer, BookingStatus $status = BookingStatus::Confirmed): Booking
    {
        return Booking::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'trip_id' => $this->tripFor($company, $customer)->id,
            'status' => $status,
        ]);
    }

    private function item(Company $company, Booking $booking, array $overrides = []): BookingItem
    {
        return BookingItem::factory()->create(array_merge([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'status' => BookingItemStatus::Confirmed,
        ], $overrides));
    }

    private function customerPayment(Company $company, Booking $booking, array $overrides = []): CustomerPayment
    {
        return CustomerPayment::factory()->create(array_merge([
            'company_id' => $company->id,
            'booking_id' => $booking->id,
            'paid_at' => now(),
        ], $overrides));
    }

    private function supplierPayment(Company $company, BookingItem $item, array $overrides = []): SupplierPayment
    {
        return SupplierPayment::factory()->create(array_merge([
            'company_id' => $company->id,
            'booking_item_id' => $item->id,
            'paid_at' => now(),
        ], $overrides));
    }

    // ---- Customer balances ----------------------------------------

    public function test_customer_with_fully_paid_booking_does_not_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Fully Paid Guy']);
        $booking = $this->booking($company, $customer);

        $this->item($company, $booking, ['sell_price' => 10000]);
        $this->customerPayment($company, $booking, ['amount' => 10000]);

        $this->actingAs($user)->get(route('money'))
            ->assertOk()
            ->assertDontSee('Fully Paid Guy');
    }

    public function test_customer_with_outstanding_booking_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Owes Money Guy']);
        $booking = $this->booking($company, $customer);

        $this->item($company, $booking, ['sell_price' => 15000]);
        $this->customerPayment($company, $booking, ['amount' => 5000]);

        $this->actingAs($user)->get(route('money'))
            ->assertOk()
            ->assertSee('Owes Money Guy');
    }

    public function test_customer_with_multiple_outstanding_bookings_is_aggregated(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Multi Booking Guy']);

        $b1 = $this->booking($company, $customer);
        $this->item($company, $b1, ['sell_price' => 10000]);

        $b2 = $this->booking($company, $customer);
        $this->item($company, $b2, ['sell_price' => 5000]);

        $response = $this->actingAs($user)->get(route('money'));

        $response->assertOk();
        $response->assertViewHas('customerBalances', function ($balances) use ($customer) {
            $row = collect($balances->items())->firstWhere('id', $customer->id);

            return $row && (float) $row->outstanding === 15000.0;
        });
    }

    public function test_customer_with_some_paid_some_unpaid_bookings_gets_correct_total(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Mixed Booking Guy']);

        $paidBooking = $this->booking($company, $customer);
        $this->item($company, $paidBooking, ['sell_price' => 8000]);
        $this->customerPayment($company, $paidBooking, ['amount' => 8000]);

        $unpaidBooking = $this->booking($company, $customer);
        $this->item($company, $unpaidBooking, ['sell_price' => 6000]);

        $response = $this->actingAs($user)->get(route('money'));

        $response->assertViewHas('customerBalances', function ($balances) use ($customer) {
            $row = collect($balances->items())->firstWhere('id', $customer->id);

            return $row && (float) $row->outstanding === 6000.0;
        });
    }

    public function test_voided_customer_payments_do_not_reduce_outstanding_balance(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Voided Payment Guy']);
        $booking = $this->booking($company, $customer);

        $this->item($company, $booking, ['sell_price' => 10000]);
        $this->customerPayment($company, $booking, ['amount' => 10000, 'voided_at' => now(), 'voided_reason' => 'Bounced']);

        $response = $this->actingAs($user)->get(route('money'));

        $response->assertViewHas('customerBalances', function ($balances) use ($customer) {
            $row = collect($balances->items())->firstWhere('id', $customer->id);

            return $row && (float) $row->outstanding === 10000.0;
        });
    }

    public function test_cancelled_booking_is_excluded_from_customer_balances(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Cancelled Booking Guy']);
        $booking = $this->booking($company, $customer, BookingStatus::Cancelled);

        $this->item($company, $booking, ['sell_price' => 10000]);

        $this->actingAs($user)->get(route('money'))
            ->assertOk()
            ->assertDontSee('Cancelled Booking Guy');
    }

    public function test_multiple_customers_sorted_by_outstanding_descending_by_default(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $small = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Small Debtor']);
        $this->item($company, $this->booking($company, $small), ['sell_price' => 1000]);

        $large = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Large Debtor']);
        $this->item($company, $this->booking($company, $large), ['sell_price' => 50000]);

        $response = $this->actingAs($user)->get(route('money'));

        $response->assertViewHas('customerBalances', function ($balances) {
            $names = collect($balances->items())->pluck('name')->values()->all();

            return $names === ['Large Debtor', 'Small Debtor'];
        });
    }

    // ---- Supplier balances ------------------------------------------

    public function test_supplier_with_fully_paid_items_does_not_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Settled Supplier']);

        $item = $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 4000]);
        $this->supplierPayment($company, $item, ['amount' => 4000]);

        $this->actingAs($user)->get(route('money'))
            ->assertOk()
            ->assertDontSee('Settled Supplier');
    }

    public function test_supplier_with_outstanding_items_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Owed Supplier']);

        $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 4000]);

        $this->actingAs($user)->get(route('money'))
            ->assertOk()
            ->assertSee('Owed Supplier');
    }

    public function test_supplier_with_multiple_items_is_aggregated(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Multi Item Supplier']);

        $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 2000]);
        $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 3000]);

        $response = $this->actingAs($user)->get(route('money'));

        $response->assertViewHas('supplierBalances', function ($balances) use ($supplier) {
            $row = collect($balances->items())->firstWhere('id', $supplier->id);

            return $row && (float) $row->payable === 5000.0;
        });
    }

    public function test_partially_paid_supplier_item_calculates_correct_payable(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Partial Pay Supplier']);

        $item = $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 9000]);
        $this->supplierPayment($company, $item, ['amount' => 3500]);

        $response = $this->actingAs($user)->get(route('money'));

        $response->assertViewHas('supplierBalances', function ($balances) use ($supplier) {
            $row = collect($balances->items())->firstWhere('id', $supplier->id);

            return $row && (float) $row->payable === 5500.0;
        });
    }

    public function test_voided_supplier_payments_do_not_reduce_payable(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Voided Supplier Payment']);

        $item = $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 6000]);
        $this->supplierPayment($company, $item, ['amount' => 6000, 'voided_at' => now(), 'voided_reason' => 'Reversed']);

        $response = $this->actingAs($user)->get(route('money'));

        $response->assertViewHas('supplierBalances', function ($balances) use ($supplier) {
            $row = collect($balances->items())->firstWhere('id', $supplier->id);

            return $row && (float) $row->payable === 6000.0;
        });
    }

    public function test_cancelled_booking_item_is_excluded_from_supplier_balances(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Cancelled Item Supplier']);

        $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 6000, 'status' => BookingItemStatus::Cancelled]);

        $this->actingAs($user)->get(route('money'))
            ->assertOk()
            ->assertDontSee('Cancelled Item Supplier');
    }

    public function test_multiple_suppliers_sorted_by_payable_descending_by_default(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);

        $small = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Small Payable']);
        $this->item($company, $booking, ['supplier_id' => $small->id, 'cost' => 500]);

        $large = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Large Payable']);
        $this->item($company, $booking, ['supplier_id' => $large->id, 'cost' => 20000]);

        $response = $this->actingAs($user)->get(route('money'));

        $response->assertViewHas('supplierBalances', function ($balances) {
            $names = collect($balances->items())->pluck('name')->values()->all();

            return $names === ['Large Payable', 'Small Payable'];
        });
    }

    // ---- Totals -------------------------------------------------------

    public function test_customer_total_equals_sum_of_displayed_balances(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        foreach (['Alpha', 'Beta', 'Gamma'] as $i => $name) {
            $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => $name]);
            $this->item($company, $this->booking($company, $customer), ['sell_price' => 1000 * ($i + 1)]);
        }

        $response = $this->actingAs($user)->get(route('money'));

        $data = $response->viewData('customerBalances');
        $total = $response->viewData('customerTotal');

        $this->assertSame((float) collect($data->items())->sum('outstanding'), $total);
        $this->assertSame(6000.0, $total);
    }

    public function test_supplier_total_equals_sum_of_displayed_balances(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);

        foreach (['SupA', 'SupB'] as $i => $name) {
            $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => $name]);
            $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 2000 * ($i + 1)]);
        }

        $response = $this->actingAs($user)->get(route('money'));

        $data = $response->viewData('supplierBalances');
        $total = $response->viewData('supplierTotal');

        $this->assertSame((float) collect($data->items())->sum('payable'), $total);
        $this->assertSame(6000.0, $total);
    }

    // ---- Tenant isolation ----------------------------------------------

    public function test_company_a_cannot_see_company_b_customer_balances(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $customerB = Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Other Tenant Customer']);
        $bookingB = $this->booking($companyB, $customerB);
        $this->item($companyB, $bookingB, ['sell_price' => 10000]);

        $this->actingAs($userA)->get(route('money'))
            ->assertOk()
            ->assertDontSee('Other Tenant Customer');
    }

    public function test_company_a_cannot_see_company_b_supplier_balances(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);
        $bookingB = $this->booking($companyB, $customerB);
        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id, 'name' => 'Other Tenant Supplier']);
        $this->item($companyB, $bookingB, ['supplier_id' => $supplierB->id, 'cost' => 5000]);

        $this->actingAs($userA)->get(route('money'))
            ->assertOk()
            ->assertDontSee('Other Tenant Supplier');
    }

    public function test_company_b_amounts_do_not_affect_company_a_totals(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        // Company A has zero balances of its own.
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);
        $bookingB = $this->booking($companyB, $customerB);
        $this->item($companyB, $bookingB, ['sell_price' => 99999]);

        $response = $this->actingAs($userA)->get(route('money'));

        $response->assertViewHas('customerTotal', 0.0);
        $response->assertViewHas('supplierTotal', 0.0);
    }

    public function test_company_b_suppliers_do_not_appear_in_company_a_list(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $customerA = Customer::factory()->create(['company_id' => $companyA->id]);
        $bookingA = $this->booking($companyA, $customerA);
        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id]);

        // Even if a Company A booking item somehow referenced a foreign
        // supplier id, the join must scope suppliers by company too.
        $this->item($companyA, $bookingA, ['supplier_id' => $supplierB->id, 'cost' => 3000]);

        $response = $this->actingAs($userA)->get(route('money'));

        $response->assertViewHas('supplierBalances', fn ($balances) => $balances->isEmpty());
    }

    // ---- Navigation -----------------------------------------------------

    public function test_customer_row_links_to_customer_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Linked Customer']);
        $booking = $this->booking($company, $customer);
        $this->item($company, $booking, ['sell_price' => 5000]);

        $this->actingAs($user)->get(route('money'))
            ->assertOk()
            ->assertSee(route('customers.show', $customer), false);
    }

    public function test_supplier_row_links_to_supplier_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Linked Supplier']);
        $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 3000]);

        $this->actingAs($user)->get(route('money'))
            ->assertOk()
            ->assertSee(route('suppliers.show', $supplier), false);
    }

    // ---- Consistency with Booking/BookingItem model calculations -------

    public function test_customer_outstanding_matches_booking_amount_remaining(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Consistency Check Customer']);
        $booking = $this->booking($company, $customer);

        $this->item($company, $booking, ['sell_price' => 12345]);
        $this->item($company, $booking, ['sell_price' => 6789]);
        $this->customerPayment($company, $booking, ['amount' => 5000]);

        $modelRemaining = $booking->fresh()->load('items', 'payments')->amountRemaining();

        $response = $this->actingAs($user)->get(route('money'));
        $data = $response->viewData('customerBalances');
        $row = collect($data->items())->firstWhere('id', $customer->id);

        $this->assertNotNull($row);
        $this->assertSame($modelRemaining, (float) $row->outstanding);
    }

    public function test_supplier_payable_matches_booking_item_amount_payable(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $booking = $this->booking($company, $customer);
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Consistency Check Supplier']);

        $item = $this->item($company, $booking, ['supplier_id' => $supplier->id, 'cost' => 17500]);
        $this->supplierPayment($company, $item, ['amount' => 4000]);

        $modelPayable = $item->fresh()->load('supplierPayments')->amountPayable();

        $response = $this->actingAs($user)->get(route('money'));
        $data = $response->viewData('supplierBalances');
        $row = collect($data->items())->firstWhere('id', $supplier->id);

        $this->assertNotNull($row);
        $this->assertSame($modelPayable, (float) $row->payable);
    }
}

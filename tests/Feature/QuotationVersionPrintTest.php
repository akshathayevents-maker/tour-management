<?php

namespace Tests\Feature;

use App\Enums\QuotationVersionStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationLineItem;
use App\Models\QuotationVersion;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuotationVersionPrintTest extends TestCase
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

    private function versionFor(Company $company, Customer $customer, QuotationVersionStatus $status, array $overrides = []): QuotationVersion
    {
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $quotation = Quotation::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);

        return QuotationVersion::factory()->create(array_merge([
            'company_id' => $company->id,
            'quotation_id' => $quotation->id,
            'status' => $status,
        ], $overrides));
    }

    private function lineItem(Company $company, QuotationVersion $version, array $overrides = []): QuotationLineItem
    {
        return QuotationLineItem::factory()->create(array_merge([
            'company_id' => $company->id,
            'quotation_version_id' => $version->id,
        ], $overrides));
    }

    // ---- Access / printable states ------------------------------------

    /**
     * @return iterable<string, array{0: QuotationVersionStatus}>
     */
    public static function printableStatusProvider(): iterable
    {
        yield 'sent' => [QuotationVersionStatus::Sent];
        yield 'accepted' => [QuotationVersionStatus::Accepted];
        yield 'rejected' => [QuotationVersionStatus::Rejected];
    }

    #[DataProvider('printableStatusProvider')]
    public function test_printable_status_can_be_printed(QuotationVersionStatus $status): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, $status);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))->assertOk();
    }

    public function test_draft_version_returns_422(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Draft);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))->assertStatus(422);
    }

    public function test_guest_cannot_access_print_page(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);

        $this->get(route('quotation-versions.print', $version))->assertRedirect(route('login'));
    }

    public function test_company_a_cannot_print_company_b_quotation_version(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);
        $versionB = $this->versionFor($companyB, $customerB, QuotationVersionStatus::Sent);

        $this->actingAs($userA)->get(route('quotation-versions.print', $versionB))->assertNotFound();
    }

    public function test_nonexistent_quotation_version_returns_404(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get('/quotation-versions/999999/print')->assertNotFound();
    }

    // ---- Content ------------------------------------------------------

    public function test_company_name_appears(): void
    {
        $company = Company::factory()->create(['name' => 'Print Test Travels']);
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('Print Test Travels');
    }

    public function test_customer_information_appears(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Quote Customer', 'phone' => '9988776655', 'email' => 'quote@example.com']);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('Quote Customer')
            ->assertSee('9988776655')
            ->assertSee('quote@example.com');
    }

    public function test_quotation_reference_and_status_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent, ['version_number' => 3]);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('Quotation V3')
            ->assertSee('Sent');
    }

    public function test_valid_until_appears_when_present(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent, ['valid_until' => '2026-12-25']);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('25 Dec 2026');
    }

    public function test_destination_and_travel_dates_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'destination' => 'Kerala Backwaters', 'start_date' => '2026-11-01', 'end_date' => '2026-11-06']);
        $quotation = Quotation::factory()->create(['company_id' => $company->id, 'trip_id' => $trip->id]);
        $version = QuotationVersion::factory()->create(['company_id' => $company->id, 'quotation_id' => $quotation->id, 'status' => QuotationVersionStatus::Sent]);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('Kerala Backwaters')
            ->assertSee('01 Nov 2026')
            ->assertSee('06 Nov 2026');
    }

    public function test_line_item_descriptions_and_pricing_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);
        $this->lineItem($company, $version, ['description' => 'Deluxe Hotel Stay', 'sell_price' => 35000]);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('Deluxe Hotel Stay')
            ->assertSee('35,000.00');
    }

    public function test_terms_appear_when_present(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent, ['terms' => '50% advance required to confirm booking.']);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('50% advance required to confirm booking.');
    }

    public function test_notes_appear_when_present(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent, ['notes' => 'Includes airport transfers both ways.']);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('Includes airport transfers both ways.');
    }

    public function test_terms_and_notes_section_is_absent_when_both_null(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent, ['terms' => null, 'notes' => null]);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertDontSee('Terms')
            ->assertDontSee('Notes');
    }

    public function test_zero_line_items_renders_gracefully(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee('No line items added yet.');
    }

    // ---- Financial consistency -----------------------------------------

    public function test_printed_total_matches_total_sell_price(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);
        $this->lineItem($company, $version, ['sell_price' => 12345]);
        $this->lineItem($company, $version, ['sell_price' => 6789]);

        $expected = $version->fresh()->load('lineItems')->totalSellPrice();

        $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->assertSee(number_format($expected, 2));

        $this->assertSame(19134.0, $expected);
    }

    // ---- Security / non-leakage -----------------------------------------

    public function test_internal_cost_and_margin_never_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);
        $this->lineItem($company, $version, ['description' => 'Hotel Package', 'sell_price' => 20000, 'cost' => 14000]);

        $response = $this->actingAs($user)->get(route('quotation-versions.print', $version));
        $response->assertOk();

        $content = $response->getContent();

        // The cost/margin figures themselves must never render.
        $this->assertStringNotContainsString('14,000.00', $content);
        $this->assertStringNotContainsString(number_format($version->fresh()->load('lineItems')->estimatedMargin() ?? -1, 2), $content);
        $this->assertStringNotContainsString('estimatedMargin', $content);
        $this->assertStringNotContainsString('totalCost', $content);
        $this->assertStringNotContainsString('Margin', $content);
        $this->assertStringNotContainsString('Cost', $content);
    }

    public function test_supplier_and_debug_data_never_appear(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);
        $this->lineItem($company, $version);

        $content = $this->actingAs($user)->get(route('quotation-versions.print', $version))
            ->assertOk()
            ->getContent();

        foreach (['supplier_payable', 'SupplierPayment', 'exception', 'stack trace', 'Illuminate\\'] as $needle) {
            $this->assertStringNotContainsString($needle, $content);
        }
    }

    // ---- Regression -----------------------------------------------------

    public function test_quotation_version_show_page_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $version = $this->versionFor($company, $customer, QuotationVersionStatus::Sent);

        $this->actingAs($user)->get(route('quotation-versions.show', $version))->assertOk();
    }

    public function test_booking_print_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $booking = \App\Models\Booking::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'trip_id' => $trip->id]);

        $this->actingAs($user)->get(route('bookings.print', $booking))->assertOk();
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

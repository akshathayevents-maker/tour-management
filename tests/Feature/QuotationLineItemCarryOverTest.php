<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Company;
use App\Models\Customer;
use App\Models\QuotationLineItem;
use App\Models\QuotationVersion;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationLineItemCarryOverTest extends TestCase
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

    private function acceptedVersionWithLines(User $user, Company $company, array $lines = []): QuotationVersion
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();

        foreach ($lines as $line) {
            $this->actingAs($user)->post(route('quotation-line-items.store', $version), $line);
        }

        if (empty($lines)) {
            // A version with zero line items cannot be Sent (existing
            // guard: "Add at least one line item before sending"), so to
            // test the zero-line-item conversion path we bypass Send/Accept
            // entirely by marking the version Accepted directly — this is
            // a test-only shortcut, not a new business rule.
            $version->update(['status' => \App\Enums\QuotationVersionStatus::Accepted]);

            return $version->fresh();
        }

        $this->actingAs($user)->post(route('quotation-versions.send', $version));
        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()));

        return $version->fresh();
    }

    public function test_every_quotation_line_item_produces_exactly_one_booking_item(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLines($user, $company, [
            ['description' => 'Hotel', 'category' => 'hotel', 'sell_price' => 30000, 'cost' => 20000],
            ['description' => 'Airport Transfer', 'category' => 'transport', 'sell_price' => 5000, 'cost' => 3000],
            ['description' => 'Sightseeing', 'category' => 'activity', 'sell_price' => 10000, 'cost' => 6000],
        ]);

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $booking = Booking::first()->fresh()->load('items');

        $this->assertSame(3, $booking->items->count());
    }

    public function test_field_mapping_matches_quotation_line_item(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLines($user, $company, [
            ['description' => 'Deluxe Hotel Stay', 'category' => 'hotel', 'sell_price' => 45000, 'cost' => 25000],
        ]);

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $item = Booking::first()->fresh()->items->first();

        $this->assertSame('Deluxe Hotel Stay', $item->description);
        $this->assertSame('hotel', $item->category->value);
        $this->assertEquals(45000, $item->sell_price);
        $this->assertEquals(25000, $item->cost);
        $this->assertNull($item->supplier_id);
    }

    public function test_line_item_order_is_preserved(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLines($user, $company, [
            ['description' => 'Hotel', 'sell_price' => 30000],
            ['description' => 'Transfer', 'sell_price' => 5000],
            ['description' => 'Sightseeing', 'sell_price' => 10000],
        ]);

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $descriptions = Booking::first()->fresh()->items->pluck('description')->values()->all();

        $this->assertSame(['Hotel', 'Transfer', 'Sightseeing'], $descriptions);
    }

    public function test_zero_line_item_quotation_converts_with_zero_booking_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLines($user, $company, []);

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version))
            ->assertRedirect();

        $this->assertSame(1, Booking::count());
        $this->assertSame(0, Booking::first()->items->count());
    }

    public function test_booking_total_sell_price_matches_sum_of_copied_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLines($user, $company, [
            ['description' => 'Hotel', 'sell_price' => 30000],
            ['description' => 'Transfer', 'sell_price' => 5000],
            ['description' => 'Sightseeing', 'sell_price' => 10000],
        ]);

        $quotationTotal = $version->fresh()->load('lineItems')->totalSellPrice();
        $this->assertSame(45000.0, $quotationTotal);

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $booking = Booking::first()->fresh()->load('items');

        $this->assertSame($quotationTotal, $booking->totalSellPrice());
        $this->assertSame(45000.0, $booking->totalSellPrice());
    }

    public function test_repeated_conversion_still_creates_a_second_booking_with_its_own_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLines($user, $company, [
            ['description' => 'Hotel', 'sell_price' => 30000],
            ['description' => 'Transfer', 'sell_price' => 5000],
        ]);

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $bookingA = Booking::first();

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version->fresh()));

        $this->assertSame(2, Booking::where('accepted_quotation_version_id', $version->id)->count());

        $bookingB = Booking::where('accepted_quotation_version_id', $version->id)
            ->where('id', '!=', $bookingA->id)->first();

        $this->assertSame(2, $bookingA->fresh()->items->count());
        $this->assertSame(2, $bookingB->fresh()->items->count());
    }

    public function test_second_conversion_does_not_modify_first_bookings_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLines($user, $company, [
            ['description' => 'Hotel', 'sell_price' => 30000],
        ]);

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $bookingA = Booking::first();
        $itemA = $bookingA->items->first();

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version->fresh()));

        $this->assertSame('Hotel', $itemA->fresh()->description);
        $this->assertEquals(30000, $itemA->fresh()->sell_price);
        $this->assertSame(1, $bookingA->fresh()->items->count());
    }

    public function test_changing_booking_item_does_not_mutate_source_quotation_line_item(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $version = $this->acceptedVersionWithLines($user, $company, [
            ['description' => 'Hotel', 'sell_price' => 30000],
        ]);
        $lineItem = QuotationLineItem::first();

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $version));
        $booking = Booking::first();
        $item = $booking->fresh()->items->first();

        $this->actingAs($user)->patch(route('booking-items.update', $item), [
            'description' => 'Hotel (Upgraded)',
            'sell_price' => 35000,
        ]);

        $this->assertSame('Hotel', $lineItem->fresh()->description);
        $this->assertEquals(30000, $lineItem->fresh()->sell_price);
        $this->assertSame('Hotel (Upgraded)', $item->fresh()->description);
    }

    public function test_company_a_cannot_convert_company_bs_quotation_version(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $userB = $this->companyUser($companyB);
        $versionB = $this->acceptedVersionWithLines($userB, $companyB, [
            ['description' => 'Secret Company B Item', 'sell_price' => 99999],
        ]);

        $this->actingAs($userA)->post(route('quotation-versions.booking.store', $versionB))
            ->assertNotFound();

        $this->assertSame(0, Booking::count());
    }

    public function test_no_n_plus_one_query_blowup_with_many_line_items(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $lines = [];
        for ($i = 1; $i <= 10; $i++) {
            $lines[] = ['description' => "Item {$i}", 'sell_price' => 1000];
        }
        $version = $this->acceptedVersionWithLines($user, $company, $lines);

        $this->actingAs($user);
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->post(route('quotation-versions.booking.store', $version));
        $queryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertSame(10, Booking::first()->fresh()->items->count());

        // A pathological N+1 (e.g. a query per item to resolve the
        // supplier, or a re-fetch of the booking per item) would push this
        // well past a generous ceiling for a 10-item conversion; one insert
        // per item plus fixed overhead stays comfortably under it.
        $this->assertLessThan(60, $queryCount, "queryCount={$queryCount}");
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\QuotationVersion;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
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

    public function test_draft_can_be_created(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('trips.quotation.store', $trip));

        $version = QuotationVersion::first();
        $response->assertRedirect(route('quotation-versions.show', $version));
        $this->assertSame(1, $version->version_number);
        $this->assertTrue($version->isEditable());
    }

    public function test_minimal_line_item_works_with_description_and_selling_price(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();

        $this->actingAs($user)->post(route('quotation-line-items.store', $version), [
            'description' => 'Hotel package',
            'sell_price' => 30000,
        ])->assertSessionDoesntHaveErrors();

        $item = $version->fresh()->lineItems->first();
        $this->assertSame('Hotel package', $item->description);
        $this->assertNull($item->cost);
    }

    public function test_cost_is_optional(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();

        $this->actingAs($user)->post(route('quotation-line-items.store', $version), [
            'description' => 'Transport',
            'sell_price' => 10000,
            'cost' => 6000,
        ]);

        $item = $version->fresh()->lineItems->first();
        $this->assertEquals(6000, $item->cost);
    }

    public function test_totals_calculate_correctly(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();

        $this->actingAs($user)->post(route('quotation-line-items.store', $version), ['description' => 'Hotel', 'sell_price' => 30000, 'cost' => 20000]);
        $this->actingAs($user)->post(route('quotation-line-items.store', $version), ['description' => 'Transport', 'sell_price' => 10000, 'cost' => 6000]);
        $this->actingAs($user)->post(route('quotation-line-items.store', $version), ['description' => 'Misc', 'sell_price' => 2000]);

        $version->refresh()->load('lineItems');
        $this->assertEquals(42000, $version->totalSellPrice());
        $this->assertEquals(26000, $version->totalCost());
        $this->assertEquals(16000, $version->estimatedMargin());
    }

    public function test_multiple_line_items_work(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();

        $this->actingAs($user)->post(route('quotation-line-items.store', $version), ['description' => 'A', 'sell_price' => 100]);
        $this->actingAs($user)->post(route('quotation-line-items.store', $version), ['description' => 'B', 'sell_price' => 200]);

        $this->assertSame(2, $version->fresh()->lineItems->count());
    }

    public function test_company_isolation_on_quotation_version(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $versionB = QuotationVersion::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->get(route('quotation-versions.show', $versionB))->assertNotFound();
    }
}

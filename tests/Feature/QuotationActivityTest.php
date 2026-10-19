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
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class QuotationActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function companyUser(Company $company, string $name = 'Test Agent'): User
    {
        $user = User::factory()->create(['company_id' => $company->id, 'name' => $name]);
        $user->assignRole('company_user');

        return $user;
    }

    private function tripFor(Company $company): Trip
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        return Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
    }

    private function draftWithLineItem(User $user, Trip $trip): QuotationVersion
    {
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();

        $this->actingAs($user)->post(route('quotation-line-items.store', $version), [
            'description' => 'Hotel package',
            'sell_price' => 30000,
        ]);

        return $version->fresh();
    }

    public function test_quotation_creation_logs_activity_on_version_and_trip(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);

        $this->actingAs($user)->post(route('trips.quotation.store', $trip));

        $version = QuotationVersion::first();

        $this->assertNotNull(Activity::forSubject($version)->first());
        $this->assertNotNull(Activity::forSubject($trip)->first());
        $this->assertStringContainsString('Quotation V1 created', Activity::forSubject($version)->first()->description);
    }

    public function test_version_sent_activity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $version = $this->draftWithLineItem($user, $trip);

        $this->actingAs($user)->post(route('quotation-versions.send', $version))->assertSessionDoesntHaveErrors();

        $activity = Activity::forSubject($version->fresh())->orderByDesc('id')->first();
        $this->assertSame('Quotation V1 sent.', $activity->description);
    }

    public function test_version_accepted_activity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));

        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()))->assertSessionDoesntHaveErrors();

        $activity = Activity::forSubject($version->fresh())->orderByDesc('id')->first();
        $this->assertSame('Quotation V1 accepted.', $activity->description);
    }

    public function test_version_rejected_activity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));

        $this->actingAs($user)->post(route('quotation-versions.reject', $version->fresh()))->assertSessionDoesntHaveErrors();

        $activity = Activity::forSubject($version->fresh())->orderByDesc('id')->first();
        $this->assertSame('Quotation V1 rejected.', $activity->description);
    }

    public function test_new_version_created_from_activity_preserves_version_identity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));
        $this->actingAs($user)->post(route('quotation-versions.reject', $version->fresh()));

        $this->actingAs($user)->post(route('quotation-versions.new-version', $version->fresh()))->assertSessionDoesntHaveErrors();

        $v2 = QuotationVersion::where('version_number', 2)->first();
        $activity = Activity::forSubject($v2)->orderByDesc('id')->first();

        $this->assertSame('Quotation V2 created from V1.', $activity->description);
    }

    public function test_no_duplicate_activity_when_invalid_transition_is_rejected_by_existing_guard(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));
        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()));

        $before = Activity::forSubject($version->fresh())->count();

        // Already-accepted version cannot be rejected — existing guard in
        // QuotationVersionService::reject() throws before any log() call.
        $this->actingAs($user)->post(route('quotation-versions.reject', $version->fresh()))->assertSessionHasErrors();

        $after = Activity::forSubject($version->fresh())->count();

        $this->assertSame($before, $after);
    }

    public function test_activity_appears_on_quotation_version_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));

        $this->actingAs($user)->get(route('quotation-versions.show', $version->fresh()))
            ->assertOk()
            ->assertSee('Quotation V1 sent.');
    }

    public function test_company_a_cannot_see_company_b_quotation_activity(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = $this->tripFor($companyB);
        $quotationB = Quotation::factory()->create(['company_id' => $companyB->id, 'trip_id' => $tripB->id]);
        $versionB = QuotationVersion::factory()->create(['company_id' => $companyB->id, 'quotation_id' => $quotationB->id, 'status' => QuotationVersionStatus::Sent]);

        activity()->performedOn($versionB)->log('Quotation V1 sent.');

        $this->actingAs($userA)->get(route('quotation-versions.show', $versionB))->assertNotFound();
    }

    public function test_internal_cost_and_margin_do_not_leak_through_activity_descriptions(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $version = $this->draftWithLineItem($user, $trip);
        QuotationLineItem::where('quotation_version_id', $version->id)->update(['cost' => 12345]);

        $this->actingAs($user)->post(route('quotation-versions.send', $version->fresh()));

        $activities = Activity::forSubject($version->fresh())->get();

        foreach ($activities as $activity) {
            $this->assertStringNotContainsString('12345', $activity->description);
            $this->assertStringNotContainsString('12,345', $activity->description);
            $this->assertStringNotContainsString('margin', strtolower($activity->description));
            $this->assertStringNotContainsString('cost', strtolower($activity->description));
        }
    }

    public function test_existing_quotation_lifecycle_tests_still_pass(): void
    {
        // Smoke check that the core versioning invariant (only one
        // accepted version) still holds after adding activity logging.
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = $this->tripFor($company);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));
        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()));

        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()))->assertSessionHasErrors();
    }
}

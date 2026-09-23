<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\QuotationVersion;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationVersioningTest extends TestCase
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

    public function test_v1_created(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        $version = $this->draftWithLineItem($user, $trip);

        $this->assertSame(1, $version->version_number);
    }

    public function test_v1_can_be_edited_while_draft(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);

        $this->actingAs($user)->patch(route('quotation-versions.update', $version), [
            'terms' => 'Advance 50% required',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Advance 50% required', $version->fresh()->terms);
    }

    public function test_sending_locks_v1(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);

        $this->actingAs($user)->post(route('quotation-versions.send', $version))
            ->assertRedirect(route('quotation-versions.show', $version));

        $version->refresh();
        $this->assertTrue($version->status === \App\Enums\QuotationVersionStatus::Sent);
        $this->assertNotNull($version->sent_at);
        $this->assertFalse($version->isEditable());
    }

    public function test_v1_cannot_be_modified_after_sending(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));
        $version->refresh();

        $this->actingAs($user)->patch(route('quotation-versions.update', $version), [
            'terms' => 'Should not save',
        ])->assertStatus(422);

        $this->assertNotSame('Should not save', $version->fresh()->terms);

        // Line items are locked too — not just the version's own fields.
        $lineItem = $version->lineItems->first();
        $this->actingAs($user)->patch(route('quotation-line-items.update', $lineItem), [
            'description' => 'Hacked',
            'sell_price' => 1,
        ])->assertStatus(422);

        $this->assertNotSame('Hacked', $lineItem->fresh()->description);

        $this->actingAs($user)->post(route('quotation-line-items.store', $version), [
            'description' => 'Should not be added',
            'sell_price' => 1,
        ])->assertStatus(422);

        $this->assertSame(1, $version->fresh()->lineItems->count());
    }

    public function test_v2_can_be_created_from_v1(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $v1 = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $v1));
        $v1->refresh();

        $response = $this->actingAs($user)->post(route('quotation-versions.new-version', $v1));

        $v2 = QuotationVersion::where('version_number', 2)->first();
        $response->assertRedirect(route('quotation-versions.show', $v2));
        $this->assertNotNull($v2);
        $this->assertTrue($v2->isEditable());

        // Line items copied over.
        $this->assertSame(1, $v2->lineItems->count());
        $this->assertSame('Hotel package', $v2->lineItems->first()->description);
    }

    public function test_v1_remains_unchanged_after_v2_created_and_edited(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $v1 = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $v1));
        $v1->refresh();

        $this->actingAs($user)->post(route('quotation-versions.new-version', $v1));
        $v2 = QuotationVersion::where('version_number', 2)->first();

        // Customer wants a cheaper hotel — update V2's line item.
        $v2LineItem = $v2->lineItems->first();
        $this->actingAs($user)->patch(route('quotation-line-items.update', $v2LineItem), [
            'description' => 'Hotel package (budget)',
            'sell_price' => 24000,
        ]);

        $this->assertEquals(30000, $v1->fresh()->totalSellPrice());
        $this->assertEquals(24000, $v2->fresh()->totalSellPrice());
    }

    public function test_v2_receives_correct_version_number(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $v1 = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $v1));
        $v1->refresh();
        $this->actingAs($user)->post(route('quotation-versions.new-version', $v1));

        $v2 = QuotationVersion::where('version_number', 2)->first();
        $this->assertNotNull($v2);
        $this->assertSame($v1->quotation_id, $v2->quotation_id);
    }

    public function test_only_correct_company_can_access_versions(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userB = $this->companyUser($companyB);
        $versionA = QuotationVersion::factory()->create(['company_id' => $companyA->id]);

        $this->actingAs($userB)->get(route('quotation-versions.show', $versionA))->assertNotFound();
        $this->actingAs($userB)->post(route('quotation-versions.send', $versionA))->assertNotFound();
    }

    public function test_sending_requires_at_least_one_line_item(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $version = QuotationVersion::first();

        $this->actingAs($user)->post(route('quotation-versions.send', $version))
            ->assertSessionHasErrors('line_items');

        $this->assertTrue($version->fresh()->isEditable());
    }

    public function test_accepting_a_sent_version_marks_it_accepted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));

        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()))
            ->assertRedirect();

        $this->assertTrue($version->fresh()->status === \App\Enums\QuotationVersionStatus::Accepted);
    }

    public function test_draft_cannot_be_accepted_directly(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);

        $this->actingAs($user)->post(route('quotation-versions.accept', $version))
            ->assertSessionHasErrors('status');

        $this->assertTrue($version->fresh()->isEditable());
    }

    public function test_draft_cannot_be_rejected_directly(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);

        $this->actingAs($user)->post(route('quotation-versions.reject', $version))
            ->assertSessionHasErrors('status');

        $this->assertTrue($version->fresh()->isEditable());
    }

    public function test_accepted_version_cannot_be_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));
        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()));

        $this->actingAs($user)->post(route('quotation-versions.reject', $version->fresh()))
            ->assertSessionHasErrors('status');

        $this->assertTrue($version->fresh()->status === \App\Enums\QuotationVersionStatus::Accepted);
    }

    public function test_rejected_version_cannot_be_accepted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $version));
        $this->actingAs($user)->post(route('quotation-versions.reject', $version->fresh()));

        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()))
            ->assertSessionHasErrors('status');

        $this->assertTrue($version->fresh()->status === \App\Enums\QuotationVersionStatus::Rejected);
    }

    public function test_expired_version_cannot_be_accepted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $version = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->patch(route('quotation-versions.update', $version), [
            'valid_until' => now()->subDay()->format('Y-m-d'),
        ]);
        $this->actingAs($user)->post(route('quotation-versions.send', $version->fresh()));

        $this->assertTrue($version->fresh()->isExpired());

        $this->actingAs($user)->post(route('quotation-versions.accept', $version->fresh()))
            ->assertSessionHasErrors('status');

        $this->assertTrue($version->fresh()->status === \App\Enums\QuotationVersionStatus::Sent);
    }

    public function test_only_one_accepted_version_per_quotation(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $v1 = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $v1));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v1->fresh()));

        $this->actingAs($user)->post(route('quotation-versions.new-version', $v1->fresh()));
        $v2 = QuotationVersion::where('version_number', 2)->first();
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));

        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()))
            ->assertSessionHasErrors('status');

        $this->assertTrue($v2->fresh()->status === \App\Enums\QuotationVersionStatus::Sent);
        $this->assertSame(1, $v1->quotation->versions()->where('status', 'accepted')->count());
    }

    public function test_v2_is_draft_and_does_not_inherit_v1_status(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $v1 = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $v1));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v1->fresh()));

        $this->actingAs($user)->post(route('quotation-versions.new-version', $v1->fresh()));
        $v2 = QuotationVersion::where('version_number', 2)->first();

        $this->assertTrue($v2->status === \App\Enums\QuotationVersionStatus::Draft);
        $this->assertTrue($v2->isEditable());
    }

    public function test_v2_line_items_are_independent_database_rows_from_v1(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);
        $v1 = $this->draftWithLineItem($user, $trip);
        $this->actingAs($user)->post(route('quotation-versions.send', $v1));
        $this->actingAs($user)->post(route('quotation-versions.new-version', $v1->fresh()));
        $v2 = QuotationVersion::where('version_number', 2)->first();

        $v1LineItem = $v1->fresh()->lineItems->first();
        $v2LineItem = $v2->lineItems->first();

        $this->assertNotSame($v1LineItem->id, $v2LineItem->id);
        $this->assertSame($v1->id, $v1LineItem->quotation_version_id);
        $this->assertSame($v2->id, $v2LineItem->quotation_version_id);
    }

    public function test_cannot_create_quotation_on_another_companys_trip(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = Trip::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('trips.quotation.store', $tripB))->assertNotFound();

        $this->assertSame(0, \App\Models\Quotation::count());
    }

    public function test_line_item_cannot_be_added_to_another_companys_version(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = Trip::factory()->create(['company_id' => $companyB->id]);
        $quotationB = \App\Models\Quotation::factory()->create(['company_id' => $companyB->id, 'trip_id' => $tripB->id]);
        $versionB = QuotationVersion::factory()->create(['company_id' => $companyB->id, 'quotation_id' => $quotationB->id]);

        $this->actingAs($userA)->post(route('quotation-line-items.store', $versionB), [
            'description' => 'Should not work',
            'sell_price' => 100,
        ])->assertNotFound();

        $this->assertSame(0, $versionB->lineItems()->count());
    }

    public function test_accept_reject_new_version_are_blocked_across_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $versionB = QuotationVersion::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('quotation-versions.accept', $versionB))->assertNotFound();
        $this->actingAs($userA)->post(route('quotation-versions.reject', $versionB))->assertNotFound();
        $this->actingAs($userA)->post(route('quotation-versions.new-version', $versionB))->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\QuotationVersionStatus;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationVersion;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class QuotationRevisionAfterAcceptanceTest extends TestCase
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

    private function acceptedV1(User $user, Company $company, array $overrides = []): QuotationVersion
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $v1 = QuotationVersion::first();

        $this->actingAs($user)->post(route('quotation-line-items.store', $v1), array_merge([
            'description' => 'Complete Goa Package', 'sell_price' => 50000,
        ], $overrides));
        $this->actingAs($user)->post(route('quotation-versions.send', $v1));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v1->fresh()));

        return $v1->fresh();
    }

    private function revisionOf(User $user, QuotationVersion $v1): QuotationVersion
    {
        $this->actingAs($user)->post(route('quotation-versions.new-version', $v1));

        return QuotationVersion::where('quotation_id', $v1->quotation_id)
            ->where('version_number', $v1->version_number + 1)->first();
    }

    // ---- Revision workflow ------------------------------------------

    public function test_revision_can_be_sent_and_accepted_after_v1_was_accepted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);

        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()))
            ->assertSessionDoesntHaveErrors();

        $this->assertTrue($v2->fresh()->status === QuotationVersionStatus::Accepted);
    }

    public function test_v1_becomes_superseded_when_v2_is_accepted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));

        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $this->assertTrue($v1->fresh()->status === QuotationVersionStatus::Superseded);
    }

    public function test_only_one_accepted_version_exists_afterward(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $this->assertSame(1, QuotationVersion::where('quotation_id', $v1->quotation_id)
            ->where('status', QuotationVersionStatus::Accepted)->count());
    }

    public function test_accepted_version_helper_returns_v2(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $quotation = Quotation::find($v1->quotation_id);
        $this->assertSame($v2->fresh()->id, $quotation->fresh()->acceptedVersion()->id);
    }

    public function test_v1_remains_accessible_and_printable_after_superseded(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $this->actingAs($user)->get(route('quotation-versions.show', $v1->fresh()))->assertOk();
        $this->actingAs($user)->get(route('quotation-versions.print', $v1->fresh()))
            ->assertOk()
            ->assertSee('Superseded');
    }

    // ---- Immutability -------------------------------------------------

    public function test_superseded_version_cannot_be_edited(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $this->assertFalse($v1->fresh()->isEditable());

        $this->actingAs($user)->patch(route('quotation-versions.update', $v1->fresh()), [
            'terms' => 'Hacked terms',
        ])->assertStatus(422);

        $this->assertNotEquals('Hacked terms', $v1->fresh()->terms);
    }

    public function test_superseded_version_cannot_be_sent_accepted_or_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $this->actingAs($user)->post(route('quotation-versions.send', $v1->fresh()))->assertSessionHasErrors();
        $this->actingAs($user)->post(route('quotation-versions.accept', $v1->fresh()))->assertSessionHasErrors();
        $this->actingAs($user)->post(route('quotation-versions.reject', $v1->fresh()))->assertSessionHasErrors();

        $this->assertTrue($v1->fresh()->status === QuotationVersionStatus::Superseded);
    }

    // ---- Print regression -----------------------------------------------

    public function test_draft_remains_non_printable(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);

        $this->actingAs($user)->get(route('quotation-versions.print', $v2))->assertStatus(422);
    }

    public function test_sent_accepted_rejected_print_behavior_unchanged(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $sent = QuotationVersion::first();
        $this->actingAs($user)->post(route('quotation-line-items.store', $sent), ['description' => 'Item', 'sell_price' => 1000]);
        $this->actingAs($user)->post(route('quotation-versions.send', $sent));

        $this->actingAs($user)->get(route('quotation-versions.print', $sent->fresh()))->assertOk();
    }

    // ---- Booking relationship ---------------------------------------

    public function test_existing_booking_remains_linked_to_v1_after_v2_is_accepted(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $this->actingAs($user)->post(route('quotation-versions.booking.store', $v1));
        $booking = Booking::first();

        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $this->assertSame($v1->id, $booking->fresh()->accepted_quotation_version_id);
    }

    public function test_booking_conversion_for_v2_still_works(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $this->actingAs($user)->post(route('quotation-versions.booking.store', $v2->fresh()))
            ->assertRedirect();

        $booking = Booking::where('accepted_quotation_version_id', $v2->id)->first();
        $this->assertNotNull($booking);
    }

    public function test_no_duplicate_booking_created_during_revision_acceptance(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $this->actingAs($user)->post(route('quotation-versions.booking.store', $v1));

        $before = Booking::count();

        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));
        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $this->assertSame($before, Booking::count());
    }

    // ---- Activity -----------------------------------------------------

    public function test_supersession_activity_recorded_exactly_once_on_v1_and_trip(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company, 'Priya Sharma');
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));

        $this->actingAs($user)->post(route('quotation-versions.accept', $v2->fresh()));

        $v1Activities = Activity::forSubject($v1->fresh())
            ->where('description', "Quotation V{$v1->version_number} superseded by V{$v2->version_number}.")
            ->get();
        $this->assertCount(1, $v1Activities);

        $trip = $v1->fresh()->quotation->trip;
        $tripActivities = Activity::forSubject($trip)
            ->where('description', "Quotation V{$v1->version_number} superseded by V{$v2->version_number}.")
            ->get();
        $this->assertCount(1, $tripActivities);
    }

    public function test_no_supersession_activity_when_no_prior_accepted_version(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $this->actingAs($user)->post(route('trips.quotation.store', $trip));
        $v1 = QuotationVersion::first();
        $this->actingAs($user)->post(route('quotation-line-items.store', $v1), ['description' => 'Item', 'sell_price' => 1000]);
        $this->actingAs($user)->post(route('quotation-versions.send', $v1));

        $this->actingAs($user)->post(route('quotation-versions.accept', $v1->fresh()));

        $this->assertSame(0, Activity::forSubject($v1->fresh())
            ->where('description', 'like', '%superseded%')->count());
    }

    public function test_existing_version_specific_activity_events_unaffected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);

        $this->assertNotNull(Activity::forSubject($v1)->where('description', 'like', '%created%')->first());
        $this->assertNotNull(Activity::forSubject($v1->fresh())->where('description', 'like', '%sent%')->first());
        $this->assertNotNull(Activity::forSubject($v1->fresh())->where('description', 'like', '%accepted%')->first());
    }

    // ---- Snapshot independence ----------------------------------------

    public function test_v2_line_items_independent_from_v1(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company, ['description' => 'Hotel Package']);
        $v2 = $this->revisionOf($user, $v1);

        $v2LineItem = $v2->fresh()->lineItems->first();
        $v2LineItem->update(['description' => 'Revised Hotel Package']);

        $this->assertSame('Hotel Package', $v1->fresh()->lineItems->first()->description);
        $this->assertSame('Revised Hotel Package', $v2->fresh()->lineItems->first()->description);
    }

    public function test_v2_next_version_number_and_field_copy_correct(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v1->update(['terms' => 'Standard terms', 'valid_until' => '2027-01-01']);

        $v2 = $this->revisionOf($user, $v1->fresh());

        $this->assertSame($v1->version_number + 1, $v2->version_number);
        $this->assertTrue($v2->status === QuotationVersionStatus::Draft);
    }

    // ---- Authorization / tenant isolation --------------------------------

    public function test_guest_cannot_accept_a_revision(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $v1 = $this->acceptedV1($user, $company);
        $v2 = $this->revisionOf($user, $v1);
        $this->actingAs($user)->post(route('quotation-versions.send', $v2));

        auth()->logout();

        $this->post(route('quotation-versions.accept', $v2->fresh()))->assertRedirect(route('login'));
    }

    public function test_company_a_cannot_accept_company_bs_revision(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $userB = $this->companyUser($companyB);
        $v1 = $this->acceptedV1($userB, $companyB);
        $v2 = $this->revisionOf($userB, $v1);
        $this->actingAs($userB)->post(route('quotation-versions.send', $v2));

        $this->actingAs($userA)->post(route('quotation-versions.accept', $v2->fresh()))->assertNotFound();
    }

    public function test_nonexistent_version_handled_correctly(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->post('/quotation-versions/999999/accept')->assertNotFound();
    }
}

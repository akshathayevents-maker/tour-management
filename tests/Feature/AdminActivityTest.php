<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function superAdmin(string $name = 'Root Admin'): User
    {
        $user = User::factory()->create(['company_id' => null, 'name' => $name]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function companyAdmin(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_admin');

        return $user;
    }

    // ---- Company activity -----------------------------------------

    public function test_company_creation_creates_exactly_one_activity(): void
    {
        $admin = $this->superAdmin('Priya Sharma');

        $this->actingAs($admin)->post(route('admin.companies.store'), [
            'name' => 'Sunrise Holidays',
        ]);

        $company = Company::first();
        $activities = Activity::forSubject($company)->get();

        $this->assertCount(1, $activities);
        $this->assertSame('Company "Sunrise Holidays" created.', $activities->first()->description);
    }

    public function test_company_creation_actor_recorded_correctly(): void
    {
        $admin = $this->superAdmin('Priya Sharma');

        $this->actingAs($admin)->post(route('admin.companies.store'), ['name' => 'Sunrise Holidays']);

        $activity = Activity::forSubject(Company::first())->first();
        $this->assertNotNull($activity->causer);
        $this->assertSame('Priya Sharma', $activity->causer->name);
    }

    public function test_company_activation_creates_exactly_one_activity(): void
    {
        $admin = $this->superAdmin();
        $company = Company::factory()->create(['name' => 'Sunrise Holidays', 'is_active' => false]);

        $this->actingAs($admin)->patch(route('admin.companies.toggle-active', $company));

        $activities = Activity::forSubject($company->fresh())->get();
        $this->assertCount(1, $activities);
        $this->assertSame('Company "Sunrise Holidays" activated.', $activities->first()->description);
        $this->assertTrue($company->fresh()->is_active);
    }

    public function test_company_deactivation_creates_exactly_one_activity(): void
    {
        $admin = $this->superAdmin();
        $company = Company::factory()->create(['name' => 'Sunrise Holidays', 'is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.companies.toggle-active', $company));

        $activities = Activity::forSubject($company->fresh())->get();
        $this->assertCount(1, $activities);
        $this->assertSame('Company "Sunrise Holidays" deactivated.', $activities->first()->description);
        $this->assertFalse($company->fresh()->is_active);
    }

    public function test_toggling_twice_records_activate_then_deactivate_not_a_duplicate(): void
    {
        // toggleActive() is a strict boolean flip, not a settable field, so
        // there is no "no-op" path through this endpoint — two consecutive
        // calls always alternate the two distinct, legitimate events
        // rather than ever repeating the same one back-to-back.
        $admin = $this->superAdmin();
        $company = Company::factory()->create(['name' => 'Sunrise Holidays', 'is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.companies.toggle-active', $company));
        $this->actingAs($admin)->patch(route('admin.companies.toggle-active', $company->fresh()));

        $activities = Activity::forSubject($company->fresh())->orderBy('id')->get();
        $this->assertCount(2, $activities);
        $this->assertSame('Company "Sunrise Holidays" deactivated.', $activities->first()->description);
        $this->assertSame('Company "Sunrise Holidays" activated.', $activities->last()->description);
    }

    // ---- User activity ------------------------------------------------

    public function test_user_provisioning_creates_exactly_one_activity(): void
    {
        Notification::fake();

        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staffer',
            'email' => 'new.staffer@example.com',
            'role' => 'company_user',
        ]);

        $user = User::where('email', 'new.staffer@example.com')->first();
        $this->assertNotNull($user);

        $activities = Activity::forSubject($user)->get();
        $this->assertCount(1, $activities);
        $this->assertSame('User "New Staffer" provisioned.', $activities->first()->description);
    }

    public function test_user_provisioning_actor_recorded_correctly(): void
    {
        Notification::fake();

        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);
        $admin->update(['name' => 'Priya Sharma']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staffer',
            'email' => 'new.staffer@example.com',
            'role' => 'company_user',
        ]);

        $user = User::where('email', 'new.staffer@example.com')->first();
        $activity = Activity::forSubject($user)->first();

        $this->assertNotNull($activity->causer);
        $this->assertSame('Priya Sharma', $activity->causer->name);
    }

    public function test_user_activation_creates_exactly_one_activity(): void
    {
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);
        $target = User::factory()->create(['company_id' => $company->id, 'name' => 'Target User', 'is_active' => false]);

        $this->actingAs($admin)->patch(route('admin.users.toggle-active', $target));

        $activities = Activity::forSubject($target->fresh())->get();
        $this->assertCount(1, $activities);
        $this->assertSame('User "Target User" activated.', $activities->first()->description);
    }

    public function test_user_deactivation_creates_exactly_one_activity(): void
    {
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);
        $target = User::factory()->create(['company_id' => $company->id, 'name' => 'Target User', 'is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.users.toggle-active', $target));

        $activities = Activity::forSubject($target->fresh())->get();
        $this->assertCount(1, $activities);
        $this->assertSame('User "Target User" deactivated.', $activities->first()->description);
    }

    public function test_user_toggling_twice_records_two_distinct_events_not_a_duplicate(): void
    {
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);
        $target = User::factory()->create(['company_id' => $company->id, 'name' => 'Target User', 'is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.users.toggle-active', $target));
        $this->actingAs($admin)->patch(route('admin.users.toggle-active', $target->fresh()));

        $activities = Activity::forSubject($target->fresh())->orderBy('id')->get();
        $this->assertCount(2, $activities);
        $this->assertSame('User "Target User" deactivated.', $activities->first()->description);
        $this->assertSame('User "Target User" activated.', $activities->last()->description);
    }

    // ---- Regression / tenant isolation --------------------------------

    public function test_existing_company_behavior_unchanged(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.companies.store'), ['name' => 'Sunrise Holidays'])
            ->assertRedirect(route('admin.companies.index'));

        $this->assertSame(1, Company::count());
    }

    public function test_existing_user_provisioning_behavior_unchanged(): void
    {
        Notification::fake();

        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staffer',
            'email' => 'new.staffer@example.com',
            'role' => 'company_user',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'new.staffer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('company_user'));
        $this->assertSame($company->id, $user->company_id);
    }

    public function test_company_admin_cannot_toggle_another_companys_user(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = $this->companyAdmin($companyA);
        $userB = User::factory()->create(['company_id' => $companyB->id, 'is_active' => true]);

        $this->actingAs($adminA)->patch(route('admin.users.toggle-active', $userB))
            ->assertNotFound();

        $this->assertTrue($userB->fresh()->is_active);
        $this->assertSame(0, Activity::forSubject($userB)->count());
    }

    public function test_company_admin_cannot_manage_companies(): void
    {
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);

        $this->actingAs($admin)->post(route('admin.companies.store'), ['name' => 'Unauthorized Co'])
            ->assertForbidden();

        $this->assertSame(1, Company::count());
    }

    public function test_lead_customer_enquiry_trip_quotation_booking_activity_logging_unaffected(): void
    {
        // Smoke check: the existing activity architecture (subject
        // resolution, causer, forSubject query) is unchanged by this
        // milestone — spot-checked via a Trip creation, which already
        // has its own dedicated test suite covering the rest.
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);
        $customer = \App\Models\Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($admin)->post(route('trips.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ]);

        $trip = \App\Models\Trip::first();
        $this->assertNotNull(Activity::forSubject($trip)->first());
    }
}

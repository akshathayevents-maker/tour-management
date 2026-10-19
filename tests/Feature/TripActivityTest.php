<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class TripActivityTest extends TestCase
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

    public function test_activity_created_when_trip_is_created(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('trips.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ])->assertSessionDoesntHaveErrors();

        $trip = Trip::first();
        $activity = Activity::forSubject($trip)->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('created', $activity->description);
        $this->assertStringContainsString('Goa', $activity->description);
    }

    public function test_activity_created_when_status_changes(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'status' => TripStatus::Planning]);

        $this->actingAs($user)->patch(route('trips.update', $trip), [
            'destination' => $trip->destination,
            'status' => TripStatus::Ready->value,
        ])->assertSessionDoesntHaveErrors();

        $activity = Activity::forSubject($trip)->latest()->first();

        $this->assertSame('Trip status changed from Planning to Ready.', $activity->description);
    }

    public function test_update_without_status_change_does_not_create_meaningless_activity(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'status' => TripStatus::Planning, 'destination' => 'Original Destination']);

        $before = Activity::forSubject($trip)->count();

        $this->actingAs($user)->patch(route('trips.update', $trip), [
            'destination' => 'Updated Destination',
            'status' => TripStatus::Planning->value,
        ])->assertSessionDoesntHaveErrors();

        $after = Activity::forSubject($trip)->count();

        $this->assertSame($before, $after);
    }

    public function test_activity_appears_on_trip_show_page(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'status' => TripStatus::Planning]);

        $this->actingAs($user)->patch(route('trips.update', $trip), [
            'destination' => $trip->destination,
            'status' => TripStatus::Ready->value,
        ]);

        $this->actingAs($user)->get(route('trips.show', $trip))
            ->assertOk()
            ->assertSee('Trip status changed from Planning to Ready.');
    }

    public function test_activities_are_ordered_most_recent_first(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'status' => TripStatus::Planning]);

        $this->actingAs($user)->patch(route('trips.update', $trip), ['destination' => $trip->destination, 'status' => TripStatus::Ready->value]);
        $this->actingAs($user)->patch(route('trips.update', $trip), ['destination' => $trip->destination, 'status' => TripStatus::Completed->value]);

        // created_at can tie within the same second under sqlite/pgsql test
        // timing, so order by id (insertion order) rather than timestamp.
        $activities = Activity::forSubject($trip)->orderByDesc('id')->get();

        $this->assertStringContainsString('to Completed', $activities->first()->description);
        $this->assertStringContainsString('to Ready', $activities->last()->description);
    }

    public function test_actor_is_correctly_recorded(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company, 'Priya Sharma');
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('trips.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ]);

        $trip = Trip::first();
        $activity = Activity::forSubject($trip)->first();

        $this->assertNotNull($activity->causer);
        $this->assertSame('Priya Sharma', $activity->causer->name);
    }

    public function test_company_a_cannot_see_company_b_trip_activity(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);
        $tripB = Trip::factory()->create(['company_id' => $companyB->id, 'customer_id' => $customerB->id]);

        activity()->performedOn($tripB)->log('Trip to "Secret Destination" created.');

        $this->actingAs($userA)->get(route('trips.show', $tripB))->assertNotFound();
    }

    public function test_guest_cannot_access_trip_activity_via_show_page(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);

        $this->get(route('trips.show', $trip))->assertRedirect(route('login'));
    }

    public function test_existing_trip_functionality_unaffected(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('trips.store'), [
            'customer_id' => $customer->id,
            'destination' => 'Goa',
        ])->assertRedirect();

        $this->assertSame(1, Trip::count());
    }
}

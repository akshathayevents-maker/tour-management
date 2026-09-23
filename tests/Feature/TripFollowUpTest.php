<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripFollowUpTest extends TestCase
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

    public function test_follow_up_can_be_created_against_a_trip(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $trip = Trip::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('follow-ups.store'), [
            'subject_type' => 'trip',
            'subject_id' => $trip->id,
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $followUp = FollowUp::first();
        $this->assertSame($trip->id, $followUp->followupable_id);
        $this->assertSame(Trip::class, $followUp->followupable_type);
    }

    public function test_company_a_cannot_create_follow_up_against_company_b_trip(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $tripB = Trip::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('follow-ups.store'), [
            'subject_type' => 'trip',
            'subject_id' => $tripB->id,
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertNotFound();

        $this->assertSame(0, FollowUp::count());
    }

    public function test_trip_follow_up_appears_on_trip_detail(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        FollowUp::factory()->for($trip, 'followupable')->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'reason' => 'Confirm final headcount',
        ]);

        $this->actingAs($user)->get(route('trips.show', $trip))
            ->assertOk()
            ->assertSee('Confirm final headcount');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\FollowUpStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpTest extends TestCase
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

    public function test_follow_up_can_be_created_against_a_lead(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $lead = Lead::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('follow-ups.store'), [
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'reason' => 'Confirm dates',
        ])->assertRedirect();

        $followUp = FollowUp::first();
        $this->assertSame($lead->id, $followUp->followupable_id);
        $this->assertSame(Lead::class, $followUp->followupable_type);
        $this->assertSame($company->id, $followUp->company_id);
        $this->assertSame($user->id, $followUp->created_by);
    }

    public function test_follow_up_can_be_completed(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $followUp = FollowUp::factory()->for($customer, 'followupable')->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->patch(route('follow-ups.complete', $followUp))->assertRedirect();

        $followUp->refresh();
        $this->assertSame(FollowUpStatus::Completed, $followUp->status);
        $this->assertNotNull($followUp->completed_at);
    }

    public function test_follow_up_can_be_rescheduled(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $followUp = FollowUp::factory()->for($customer, 'followupable')->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'due_at' => now()->addDay(),
        ]);

        $newDate = now()->addWeek()->format('Y-m-d H:i:s');

        $this->actingAs($user)->patch(route('follow-ups.reschedule', $followUp), [
            'due_at' => $newDate,
        ])->assertRedirect();

        $this->assertSame($newDate, $followUp->fresh()->due_at->format('Y-m-d H:i:s'));
    }

    public function test_overdue_is_derived_not_stored(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $overdue = FollowUp::factory()->for($customer, 'followupable')->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'due_at' => now()->subDay(),
        ]);

        $future = FollowUp::factory()->for($customer, 'followupable')->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'due_at' => now()->addDay(),
        ]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($future->isOverdue());

        $this->actingAs($user);
        $this->assertTrue(FollowUp::query()->overdue()->whereKey($overdue->id)->exists());
        $this->assertFalse(FollowUp::query()->overdue()->whereKey($future->id)->exists());
    }

    public function test_company_isolation_on_follow_ups(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);

        $customerB = Customer::factory()->create(['company_id' => $companyB->id]);
        $followUpB = FollowUp::factory()->for($customerB, 'followupable')->create([
            'company_id' => $companyB->id,
        ]);

        $this->actingAs($userA)
            ->patch(route('follow-ups.complete', $followUpB))
            ->assertNotFound();

        $this->assertSame(FollowUpStatus::Pending, $followUpB->fresh()->status);
    }

    public function test_cannot_schedule_follow_up_against_another_companys_record(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = $this->companyUser($companyA);
        $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)->post(route('follow-ups.store'), [
            'subject_type' => 'lead',
            'subject_id' => $leadB->id,
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertNotFound();

        $this->assertSame(0, FollowUp::count());
    }
}

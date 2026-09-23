<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function companyAdmin(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_admin');

        return $user;
    }

    private function companyUser(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_user');

        return $user;
    }

    public function test_super_admin_can_access_every_company(): void
    {
        $a = Company::factory()->create();
        $b = Company::factory()->create();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.companies.index'))
            ->assertOk()
            ->assertSee($a->name)
            ->assertSee($b->name);
    }

    public function test_company_admin_can_manage_own_company_users_only(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $adminA = $this->companyAdmin($companyA);
        $userA = $this->companyUser($companyA);
        $userB = $this->companyUser($companyB);

        $response = $this->actingAs($adminA)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee($userA->email)
            ->assertDontSee($userB->email);
    }

    public function test_company_user_cannot_access_staff_management(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->actingAs($user)->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_company_a_cannot_view_company_b_records_via_route_model_binding(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $adminA = $this->companyAdmin($companyA);
        $userB = $this->companyUser($companyB);

        // Company A admin tries to toggle a Company B user by guessing/tampering the ID.
        $this->actingAs($adminA)
            ->patch(route('admin.users.toggle-active', $userB))
            ->assertNotFound();

        $this->assertTrue($userB->fresh()->is_active);
    }

    public function test_company_admin_cannot_access_platform_company_management(): void
    {
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);

        $this->actingAs($admin)->get(route('admin.companies.index'))
            ->assertForbidden();

        $this->actingAs($admin)->get(route('admin.companies.create'))
            ->assertForbidden();
    }

    public function test_changing_id_in_url_cannot_bypass_authorization_for_company_edit(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $adminA = $this->companyAdmin($companyA);

        // Even though company_admin route middleware already blocks this,
        // this proves the policy itself (not just the middleware) rejects it.
        $this->assertFalse($adminA->can('update', $companyB));
    }

    public function test_deactivated_company_blocks_its_users(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $company->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_deactivated_user_is_blocked_even_if_company_active(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);
        $user->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_requires_valid_credentials(): void
    {
        $company = Company::factory()->create();
        $user = $this->companyUser($company);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyUserTest extends TestCase
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

    public function test_companies_search_filters_by_name(): void
    {
        Company::factory()->create(['name' => 'Smoke Co']);
        Company::factory()->create(['name' => 'Unrelated Tours']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.companies.index', ['q' => 'Smoke']))
            ->assertOk()
            ->assertSee('Smoke Co')
            ->assertDontSee('Unrelated Tours');
    }

    public function test_companies_index_paginates(): void
    {
        Company::factory()->count(25)->create();

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.companies.index'));

        $response->assertOk();
        $this->assertCount(20, $response->viewData('companies'));
        $this->assertSame(25, $response->viewData('companies')->total());
    }

    public function test_users_search_filters_by_name_and_email(): void
    {
        $company = Company::factory()->create();
        $match = User::factory()->create(['company_id' => $company->id, 'name' => 'Priya Sharma', 'email' => 'priya@example.com']);
        $other = User::factory()->create(['company_id' => $company->id, 'name' => 'Someone Else', 'email' => 'someone@example.com']);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.users.index', ['q' => 'priya']));

        $response->assertOk()
            ->assertSee($match->email)
            ->assertDontSee($other->email);
    }

    public function test_users_index_paginates(): void
    {
        $company = Company::factory()->create();
        $superAdmin = $this->superAdmin();
        User::factory()->count(25)->create(['company_id' => $company->id]);

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        $this->assertCount(20, $response->viewData('users'));
        // Super admin's own account is also a User row and appears in its own listing.
        $this->assertSame(26, $response->viewData('users')->total());
    }

    public function test_role_label_is_presentation_only_and_does_not_change_underlying_role(): void
    {
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.users.index'));

        $response->assertOk()->assertSee('Company Admin');
        $this->assertTrue($admin->fresh()->hasRole('company_admin'));
    }

    public function test_super_admin_can_toggle_company_active_state(): void
    {
        $company = Company::factory()->create(['is_active' => true]);

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.companies.toggle-active', $company))
            ->assertRedirect();

        $this->assertFalse($company->fresh()->is_active);
    }

    public function test_company_admin_can_toggle_own_company_user_active_state(): void
    {
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);
        $staff = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $staff->assignRole('company_user');

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $staff))
            ->assertRedirect();

        $this->assertFalse($staff->fresh()->is_active);
    }

    public function test_company_admin_cannot_toggle_super_admin_company_state(): void
    {
        $company = Company::factory()->create();
        $admin = $this->companyAdmin($company);

        $this->actingAs($admin)
            ->patch(route('admin.companies.toggle-active', $company))
            ->assertForbidden();
    }

    public function test_company_index_renders_both_desktop_and_mobile_toggle_controls(): void
    {
        $company = Company::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.companies.index'));

        $response->assertOk();
        // Each toggle control is rendered once for desktop and once for mobile,
        // and the word "Deactivate" appears both in the button label and in
        // its confirm() dialog text, so one toggleable row yields 4 matches.
        $this->assertSame(4, substr_count($response->getContent(), 'Deactivate'));
    }

    public function test_users_index_renders_both_desktop_and_mobile_toggle_controls(): void
    {
        $company = Company::factory()->create();
        $staff = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $staff->assignRole('company_user');

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.users.index'));

        $response->assertOk();
        // Two active, toggleable rows (the seeded staff member and the super
        // admin's own account) at 4 matches each (see note above).
        $this->assertSame(8, substr_count($response->getContent(), 'Deactivate'));
    }
}

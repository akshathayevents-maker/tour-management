<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\UserProvisioningService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ---- Root cause regression -----------------------------------------

    /**
     * The actual bug: ResetPassword::toMail() calls route('password.reset'),
     * which previously did not exist. Notification::fake() never invokes
     * toMail(), so existing provisioning tests passed while production
     * provisioning threw RouteNotFoundException inside the DB transaction
     * and rolled back the whole user creation. This test deliberately does
     * NOT fake notifications, so it exercises the real toMail() call path.
     */
    public function test_staff_provisioning_does_not_throw_when_sending_the_real_notification(): void
    {
        $company = Company::factory()->create();

        $user = app(UserProvisioningService::class)->createStaffUser([
            'name' => 'Real Notification Staffer',
            'email' => 'real.notification@example.com',
            'role' => 'company_user',
            'company_id' => $company->id,
        ]);

        $this->assertNotNull($user->id);
        $this->assertTrue($user->hasRole('company_user'));
        $this->assertSame($company->id, $user->company_id);
    }

    public function test_password_reset_route_is_registered_and_named_correctly(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('password.reset'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('password.update'));
    }

    public function test_reset_password_notification_generates_a_url_on_the_password_reset_route(): void
    {
        Notification::fake();

        $company = Company::factory()->create();
        $user = app(UserProvisioningService::class)->createStaffUser([
            'name' => 'URL Check Staffer',
            'email' => 'url.check@example.com',
            'role' => 'company_user',
            'company_id' => $company->id,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);
            $actionUrl = $mail->actionUrl;

            $this->assertStringContainsString('/reset-password/', $actionUrl);
            $this->assertStringContainsString(urlencode($user->email), $actionUrl);

            return true;
        });
    }

    // ---- Reset flow -------------------------------------------------------

    public function test_user_can_reset_password_with_a_valid_token_and_then_log_in(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertTrue(password_verify('new-strong-password', $user->fresh()->password));

        $login = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'new-strong-password',
        ]);

        $login->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $response = $this->from(route('password.reset', ['token' => 'not-a-real-token']))
            ->post(route('password.update'), [
                'token' => 'not-a-real-token',
                'email' => $user->email,
                'password' => 'new-strong-password',
                'password_confirmation' => 'new-strong-password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(password_verify('old-password', $user->fresh()->password));
    }

    public function test_reset_fails_with_an_expired_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $token = Password::createToken($user);

        // Backdate the token beyond the broker's configured expiry (60
        // minutes, config/auth.php) so Password::reset() rejects it as
        // expired via the framework's own expiry check.
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(120)]);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(password_verify('old-password', $user->fresh()->password));
    }

    public function test_reset_token_is_consumed_after_a_successful_reset(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        // Re-using the same token a second time must fail — it was
        // consumed (deleted) by the first successful reset.
        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_existing_staff_provisioning_and_role_assignment_behavior_is_unchanged(): void
    {
        Notification::fake();

        $company = Company::factory()->create();

        $this->actingAs($this->companyAdmin($company))->post(route('admin.users.store'), [
            'name' => 'Unaffected Staffer',
            'email' => 'unaffected.staffer@example.com',
            'role' => 'company_user',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'unaffected.staffer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('company_user'));
        $this->assertSame($company->id, $user->company_id);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    private function companyAdmin(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company_admin');

        return $user;
    }
}

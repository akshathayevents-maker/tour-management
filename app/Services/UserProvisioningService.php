<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Creates staff accounts (company admins/users). The admin never types a
 * password for someone else; we set a random one and send a reset link so
 * the new user picks their own on first login.
 */
class UserProvisioningService
{
    public function createStaffUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            /** @var User $user */
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'company_id' => $data['company_id'] ?? null,
                'password' => Str::random(40),
            ]);

            $user->assignRole($data['role']);

            $user->notify(new ResetPassword(Password::broker()->createToken($user)));

            return $user;
        });
    }
}

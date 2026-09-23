<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Base role set. Permissions are intentionally not fleshed out yet —
 * business modules (leads, bookings, ...) will register their own
 * permissions as they're built, per-role, without touching this seeder's
 * structure.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'company_admin', 'company_user'] as $role) {
            Role::findOrCreate($role);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Create the base roles and an initial Admin account from ADMIN_* env vars.
     * Safe to run on every deploy: existing records are left untouched.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Firefighter', 'guard_name' => 'web']);

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set, skipping admin account.');
            return;
        }

        $admin = User::firstOrNew(['email' => $email]);

        if (! $admin->exists) {
            $admin->name = env('ADMIN_NAME', 'Administrator');
            $admin->password = $password; // hashed by the User model cast
            $admin->rank = 'Admin';
            $admin->email_verified_at = now();
            $admin->save();
        }

        if (! $admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }
    }
}

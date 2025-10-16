<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // legacy model for backward compatibility (role column)
use App\Models\NewUser; // new auth model
use App\Models\NewRole;
use App\Models\UserRoleAssignment;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@pnsdhampur.local';

        // Create or update via NewUser to align with new auth fields
        $newUser = NewUser::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('Password123'),
                // New auth flags
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]
        );

        // Also ensure legacy User row exists for areas still bound to App\Models\User
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Password123'),
                'role' => 'admin',
            ]
        );

        // Assign a primary NewRole (prefer SUPER_ADMIN if available, else ADMIN)
        $role = NewRole::where('name', NewRole::SUPER_ADMIN)->first()
            ?? NewRole::where('name', NewRole::ADMIN)->first();

        if ($role) {
            UserRoleAssignment::assignRole([
                'user_id' => $newUser->id,
                'role_id' => $role->id,
                'is_active' => true,
                'is_primary' => true,
                'assigned_by' => $newUser->id,
            ]);
        }

        $this->command?->info('Admin user ensured with verified email and primary role assignment.');
    }
}

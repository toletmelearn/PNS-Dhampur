<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Check if admin user already exists
        if (!User::where('email', 'admin@pnsdhampur.local')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@pnsdhampur.local',
                'password' => bcrypt('Password123'),
                'role' => 'admin'
            ]);
            
            $this->command->info('Admin user created successfully!');
        } else {
            $this->command->info('Admin user already exists, skipping...');
        }
    }
}

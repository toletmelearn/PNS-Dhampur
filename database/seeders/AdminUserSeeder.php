<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@pnsdhampur.local',
            'password' => bcrypt('Password123'),
            'role' => 'admin'
        ]);
    }
}

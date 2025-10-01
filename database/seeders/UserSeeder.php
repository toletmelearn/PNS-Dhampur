<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@pns-dhampur.edu',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        // Create teacher users
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => 'Teacher ' . $i,
                'email' => 'teacher' . $i . '@pns-dhampur.edu',
                'password' => Hash::make('password'),
                'role' => 'teacher'
            ]);
        }

        $this->command->info('Created 1 admin and 10 teachers');
    }
}
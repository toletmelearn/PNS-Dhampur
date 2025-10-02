<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StudentUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create student users for testing
        $students = [
            [
                'name' => 'Student One',
                'email' => 'student1@pns-dhampur.edu',
                'role' => 'student'
            ],
            [
                'name' => 'Student Two', 
                'email' => 'student2@pns-dhampur.edu',
                'role' => 'student'
            ],
            [
                'name' => 'Principal User',
                'email' => 'principal@pns-dhampur.edu', 
                'role' => 'principal'
            ]
        ];

        foreach ($students as $studentData) {
            // Check if user already exists
            if (!User::where('email', $studentData['email'])->exists()) {
                User::create([
                    'name' => $studentData['name'],
                    'email' => $studentData['email'],
                    'password' => Hash::make('password123'),
                    'role' => $studentData['role'],
                ]);
                
                $this->command->info("Created {$studentData['role']}: {$studentData['email']}");
            } else {
                $this->command->info("{$studentData['email']} already exists, skipping...");
            }
        }
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user for login testing';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if test user already exists
        $existingUser = User::where('email', 'test@teacher.com')->first();
        
        if ($existingUser) {
            $this->info('Test user already exists!');
            $this->info('Email: test@teacher.com');
            $this->info('Password: password123');
            return Command::SUCCESS;
        }

        // Create test user
        $user = User::create([
            'name' => 'Test Teacher',
            'email' => 'test@teacher.com',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'phone' => '1234567890',
            'email_verified_at' => now(),
        ]);

        $this->info('Test user created successfully!');
        $this->info('Email: test@teacher.com');
        $this->info('Password: password123');
        $this->info('Role: teacher');

        return Command::SUCCESS;
    }
}

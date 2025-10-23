<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Super Admin user for testing';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if admin already exists
        $existingAdmin = User::where('role', 'admin')->first();
        
        if ($existingAdmin) {
            $this->info('Admin user already exists:');
            $this->info('Name: ' . $existingAdmin->name);
            $this->info('Email: ' . $existingAdmin->email);
            return Command::SUCCESS;
        }

        // Create admin user
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->info('Admin user created successfully!');
        $this->info('Email: superadmin@test.com');
        $this->info('Password: password123');
        $this->info('Role: admin');

        return Command::SUCCESS;
    }
}

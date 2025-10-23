<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewUser;
use App\Models\NewRole;
use Illuminate\Support\Facades\Hash;

class CreateNewAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:new-admin {--email=admin@pnsdhampur.com} {--name=Admin User} {--password=admin123}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user using the NewUser model and role system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->option('email');
        $name = $this->option('name');
        $password = $this->option('password');

        // Check if user already exists
        $existingUser = NewUser::where('email', $email)->first();
        if ($existingUser) {
            $this->error("User with email {$email} already exists!");
            return Command::FAILURE;
        }

        // Check if admin role exists
        $adminRole = NewRole::where('name', NewRole::ADMIN)->first();
        if (!$adminRole) {
            $this->error("Admin role not found in new_roles table!");
            return Command::FAILURE;
        }

        try {
            // Create user with admin role
            $user = NewUser::createWithRole([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'username' => 'admin_' . time(),
                'status' => NewUser::STATUS_ACTIVE,
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
                'created_by' => 1, // System user
            ], NewRole::ADMIN, null, [
                'assigned_by' => 1, // System user
            ]);

            $this->info("Admin user created successfully!");
            $this->info("Name: {$user->name}");
            $this->info("Email: {$user->email}");
            $this->info("Username: {$user->username}");
            $this->info("Role: " . $user->getPrimaryRole()->display_name);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create admin user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

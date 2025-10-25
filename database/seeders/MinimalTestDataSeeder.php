<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MinimalTestDataSeeder extends Seeder
{
    public function run()
    {
        $this->createRoles();
        $this->createSchools();
        $this->createUsers();
        $this->createUserRoleAssignments();
    }

    private function createRoles()
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'System Super Administrator',
                'permissions' => json_encode(['*']),
                'level' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'School Administrator',
                'permissions' => json_encode(['admin.*']),
                'level' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Teacher',
                'description' => 'School Teacher',
                'permissions' => json_encode(['teacher.*']),
                'level' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'School Student',
                'permissions' => json_encode(['student.*']),
                'level' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore($role);
        }
    }

    private function createSchools()
    {
        $schools = [
            [
                'name' => 'PNS Dhampur',
                'code' => 'PNS001',
                'address' => 'Dhampur, Uttar Pradesh',
                'phone' => '01234567890',
                'email' => 'info@pnsdhampur.edu',
                'website' => 'https://pnsdhampur.edu',
                'principal_name' => 'Dr. Principal Name',
                'established_date' => '1990-01-01',
                'status' => 'active',
                'settings' => json_encode(['timezone' => 'Asia/Kolkata']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        foreach ($schools as $school) {
            DB::table('schools')->insertOrIgnore($school);
        }
    }

    private function createUsers()
    {
        $users = [
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'superadmin@pnsdhampur.com',
                'employee_id' => 'EMP001',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543210',
                'role' => 'super_admin',
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'School Admin',
                'username' => 'admin',
                'email' => 'admin@pnsdhampur.com',
                'employee_id' => 'EMP002',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543211',
                'role' => 'admin',
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'John Teacher',
                'username' => 'teacher1',
                'email' => 'teacher1@pnsdhampur.com',
                'employee_id' => 'EMP003',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543212',
                'role' => 'teacher',
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jane Student',
                'username' => 'student1',
                'email' => 'student1@pnsdhampur.com',
                'employee_id' => 'STU001',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => '9876543213',
                'role' => 'student',
                'is_active' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($users as $user) {
            DB::table('users')->insertOrIgnore($user);
        }
    }

    private function createUserRoleAssignments()
    {
        // Check if user_roles table exists
        if (DB::getSchemaBuilder()->hasTable('user_roles')) {
            $userRoles = [
                ['user_id' => 1, 'role_id' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['user_id' => 2, 'role_id' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['user_id' => 3, 'role_id' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['user_id' => 4, 'role_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ];
            
            foreach ($userRoles as $userRole) {
                DB::table('user_roles')->insertOrIgnore($userRole);
            }
        }
    }
}
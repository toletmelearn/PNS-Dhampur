<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NewRole;

class NewRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => NewRole::SUPER_ADMIN,
                'display_name' => 'Super Admin',
                'hierarchy_level' => 100,
                'is_system_role' => true,
                'is_active' => true,
                'sort_order' => 1,
                'can_create_users' => true,
                'can_create_roles' => ['admin', 'principal', 'teacher', 'student', 'parent'],
                'permissions' => [],
                'default_permissions' => [],
            ],
            [
                'name' => NewRole::ADMIN,
                'display_name' => 'Admin',
                'hierarchy_level' => 90,
                'is_system_role' => true,
                'is_active' => true,
                'sort_order' => 2,
                'can_create_users' => true,
                'can_create_roles' => ['principal', 'teacher', 'student', 'parent'],
                'permissions' => [],
                'default_permissions' => [],
            ],
            [
                'name' => NewRole::PRINCIPAL,
                'display_name' => 'Principal',
                'hierarchy_level' => 80,
                'is_system_role' => true,
                'is_active' => true,
                'sort_order' => 3,
                'can_create_users' => true,
                'can_create_roles' => ['teacher', 'student', 'parent'],
                'permissions' => [],
                'default_permissions' => [],
            ],
            [
                'name' => NewRole::TEACHER,
                'display_name' => 'Teacher',
                'hierarchy_level' => 50,
                'is_system_role' => true,
                'is_active' => true,
                'sort_order' => 4,
                'can_create_users' => false,
                'can_create_roles' => [],
                'permissions' => [],
                'default_permissions' => [],
            ],
            [
                'name' => NewRole::STUDENT,
                'display_name' => 'Student',
                'hierarchy_level' => 10,
                'is_system_role' => true,
                'is_active' => true,
                'sort_order' => 5,
                'can_create_users' => false,
                'can_create_roles' => [],
                'permissions' => [],
                'default_permissions' => [],
            ],
            [
                'name' => NewRole::PARENT,
                'display_name' => 'Parent',
                'hierarchy_level' => 20,
                'is_system_role' => true,
                'is_active' => true,
                'sort_order' => 6,
                'can_create_users' => false,
                'can_create_roles' => [],
                'permissions' => [],
                'default_permissions' => [],
            ],
        ];

        foreach ($roles as $index => $data) {
            NewRole::updateOrCreate(
                ['name' => $data['name']],
                [
                    'display_name' => $data['display_name'],
                    'hierarchy_level' => $data['hierarchy_level'],
                    'is_system_role' => $data['is_system_role'],
                    'is_active' => $data['is_active'],
                    'sort_order' => $data['sort_order'] ?? ($index + 1),
                    'can_create_users' => $data['can_create_users'],
                    'can_create_roles' => $data['can_create_roles'],
                    'permissions' => $data['permissions'] ?? [],
                    'default_permissions' => $data['default_permissions'] ?? [],
                ]
            );
        }

        $this->command?->info('New roles seeded (hierarchy and capabilities set).');
    }
}
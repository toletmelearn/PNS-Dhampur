<?php

namespace App\Services;

use App\Models\NewRole;
use App\Models\NewUser;
use App\Models\UserRoleAssignment;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RoleService
{
    /**
     * Create a new role with permissions
     */
    public function createRole(array $data): NewRole
    {
        DB::beginTransaction();
        
        try {
            $role = NewRole::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
                'hierarchy_level' => $data['hierarchy_level'],
                'permissions' => $data['permissions'] ?? [],
                'default_permissions' => $data['default_permissions'] ?? [],
                'can_create_users' => $data['can_create_users'] ?? false,
                'can_assign_roles' => $data['can_assign_roles'] ?? false,
                'is_system_role' => $data['is_system_role'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            // Assign permissions if provided
            if (!empty($data['permission_ids'])) {
                $permissions = Permission::whereIn('id', $data['permission_ids'])->get();
                $role->permissions()->sync($permissions);
            }

            DB::commit();
            
            Log::info('Role created successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'created_by' => auth()->id()
            ]);

            return $role;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create role', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update role with permissions
     */
    public function updateRole(NewRole $role, array $data): NewRole
    {
        DB::beginTransaction();
        
        try {
            $role->update([
                'display_name' => $data['display_name'] ?? $role->display_name,
                'description' => $data['description'] ?? $role->description,
                'permissions' => $data['permissions'] ?? $role->permissions,
                'default_permissions' => $data['default_permissions'] ?? $role->default_permissions,
                'can_create_users' => $data['can_create_users'] ?? $role->can_create_users,
                'can_assign_roles' => $data['can_assign_roles'] ?? $role->can_assign_roles,
                'is_active' => $data['is_active'] ?? $role->is_active,
                'sort_order' => $data['sort_order'] ?? $role->sort_order,
            ]);

            // Update permissions if provided
            if (isset($data['permission_ids'])) {
                $permissions = Permission::whereIn('id', $data['permission_ids'])->get();
                $role->permissions()->sync($permissions);
            }

            DB::commit();
            
            Log::info('Role updated successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'updated_by' => auth()->id()
            ]);

            return $role->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(NewUser $user, NewRole $role, array $options = []): UserRoleAssignment
    {
        // Validate hierarchy - user can only assign roles at or below their level
        $currentUser = auth()->user();
        if ($currentUser && !$this->canAssignRole($currentUser, $role)) {
            throw new \Exception('You do not have permission to assign this role.');
        }

        DB::beginTransaction();
        
        try {
            // Check if user already has this role
            $existingAssignment = UserRoleAssignment::where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->where('school_id', $options['school_id'] ?? null)
                ->where('is_active', true)
                ->first();

            if ($existingAssignment) {
                throw new \Exception('User already has this role assigned.');
            }

            $assignment = UserRoleAssignment::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'school_id' => $options['school_id'] ?? null,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'starts_at' => $options['starts_at'] ?? now(),
                'expires_at' => $options['expires_at'] ?? null,
                'is_primary' => $options['is_primary'] ?? false,
                'is_temporary' => $options['is_temporary'] ?? false,
                'is_active' => true,
                'notes' => $options['notes'] ?? null,
            ]);

            // If this is set as primary, update other assignments
            if ($options['is_primary'] ?? false) {
                UserRoleAssignment::where('user_id', $user->id)
                    ->where('id', '!=', $assignment->id)
                    ->update(['is_primary' => false]);
            }

            DB::commit();
            
            Log::info('Role assigned to user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'assignment_id' => $assignment->id,
                'assigned_by' => auth()->id()
            ]);

            return $assignment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign role to user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(NewUser $user, NewRole $role, $schoolId = null): bool
    {
        $currentUser = auth()->user();
        if ($currentUser && !$this->canAssignRole($currentUser, $role)) {
            throw new \Exception('You do not have permission to remove this role.');
        }

        DB::beginTransaction();
        
        try {
            $assignment = UserRoleAssignment::where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->first();

            if (!$assignment) {
                throw new \Exception('Role assignment not found.');
            }

            $assignment->update([
                'is_active' => false,
                'revoked_by' => auth()->id(),
                'revoked_at' => now(),
                'revocation_reason' => 'Manually removed by administrator'
            ]);

            DB::commit();
            
            Log::info('Role removed from user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'assignment_id' => $assignment->id,
                'revoked_by' => auth()->id()
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove role from user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if current user can assign a specific role
     */
    public function canAssignRole(NewUser $user, NewRole $role): bool
    {
        // Super Admin can assign any role
        if ($user->hasRole(NewRole::SUPER_ADMIN)) {
            return true;
        }

        $userHighestLevel = $user->getHighestRoleLevel();
        
        // User can only assign roles at lower hierarchy levels
        return $userHighestLevel < $role->hierarchy_level;
    }

    /**
     * Get roles that a user can assign
     */
    public function getAssignableRoles(NewUser $user): \Illuminate\Database\Eloquent\Collection
    {
        if ($user->hasRole(NewRole::SUPER_ADMIN)) {
            return NewRole::active()->orderBy('hierarchy_level')->get();
        }

        $userHighestLevel = $user->getHighestRoleLevel();
        
        return NewRole::active()
            ->where('hierarchy_level', '>', $userHighestLevel)
            ->orderBy('hierarchy_level')
            ->get();
    }

    /**
     * Get role hierarchy tree
     */
    public function getRoleHierarchy(): array
    {
        $roles = NewRole::active()
            ->orderBy('hierarchy_level')
            ->orderBy('sort_order')
            ->get();

        $hierarchy = [];
        foreach ($roles as $role) {
            $hierarchy[$role->hierarchy_level][] = [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'user_count' => $role->users()->count(),
                'permissions_count' => $role->permissions()->count(),
            ];
        }

        return $hierarchy;
    }

    /**
     * Validate role hierarchy constraints
     */
    public function validateRoleHierarchy(NewRole $role, NewUser $user): bool
    {
        $userRoles = $user->getActiveRoles();
        
        foreach ($userRoles as $userRole) {
            // User cannot have roles at the same level unless specifically allowed
            if ($userRole->hierarchy_level === $role->hierarchy_level) {
                return false;
            }
            
            // User cannot have a higher level role if they have a lower level role
            if ($userRole->hierarchy_level > $role->hierarchy_level) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get default permissions for a role
     */
    public function getDefaultPermissions(NewRole $role): array
    {
        $defaultPermissions = [];

        switch ($role->name) {
            case NewRole::SUPER_ADMIN:
                $defaultPermissions = Permission::all()->pluck('name')->toArray();
                break;
                
            case NewRole::ADMIN:
                $defaultPermissions = [
                    'user.view', 'user.create', 'user.edit', 'user.delete',
                    'role.view', 'role.create', 'role.edit',
                    'school.view', 'school.create', 'school.edit', 'school.delete',
                    'teacher.view', 'teacher.create', 'teacher.edit', 'teacher.delete',
                    'student.view', 'student.create', 'student.edit', 'student.delete',
                    'parent.view', 'parent.create', 'parent.edit', 'parent.delete',
                    'report.view', 'report.generate',
                    'system.view', 'system.edit'
                ];
                break;
                
            case NewRole::PRINCIPAL:
                $defaultPermissions = [
                    'teacher.view', 'teacher.create', 'teacher.edit',
                    'student.view', 'student.create', 'student.edit',
                    'parent.view', 'parent.create', 'parent.edit',
                    'class.view', 'class.create', 'class.edit',
                    'subject.view', 'subject.create', 'subject.edit',
                    'attendance.view', 'attendance.manage',
                    'grade.view', 'grade.manage',
                    'report.view', 'report.generate'
                ];
                break;
                
            case NewRole::TEACHER:
                $defaultPermissions = [
                    'student.view',
                    'parent.view',
                    'class.view',
                    'subject.view',
                    'attendance.view', 'attendance.take',
                    'grade.view', 'grade.enter',
                    'report.view'
                ];
                break;
                
            case NewRole::STUDENT:
                $defaultPermissions = [
                    'profile.view', 'profile.edit',
                    'attendance.view',
                    'grade.view',
                    'assignment.view'
                ];
                break;
                
            case NewRole::PARENT:
                $defaultPermissions = [
                    'profile.view', 'profile.edit',
                    'child.view',
                    'attendance.view',
                    'grade.view',
                    'teacher.contact'
                ];
                break;
        }

        return $defaultPermissions;
    }

    /**
     * Sync role permissions with default permissions
     */
    public function syncRolePermissions(NewRole $role): void
    {
        $defaultPermissions = $this->getDefaultPermissions($role);
        $permissions = Permission::whereIn('name', $defaultPermissions)->get();
        // Ensure syncing sets pivot 'is_granted' so role->grantedPermissions works
        $syncPayload = [];
        foreach ($permissions as $permission) {
            $syncPayload[$permission->id] = ['is_granted' => true];
        }

        $role->permissions()->sync($syncPayload);
        $role->update(['default_permissions' => $defaultPermissions]);
        
        Log::info('Role permissions synced', [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'permissions_count' => count($defaultPermissions)
        ]);
    }

    /**
     * Get role statistics
     */
    public function getRoleStatistics(): array
    {
        return [
            'total_roles' => NewRole::count(),
            'active_roles' => NewRole::active()->count(),
            'system_roles' => NewRole::where('is_system_role', true)->count(),
            'custom_roles' => NewRole::where('is_system_role', false)->count(),
            'role_assignments' => UserRoleAssignment::active()->count(),
            'users_with_roles' => NewUser::whereHas('roleAssignments', function($q) {
                $q->where('is_active', true);
            })->count(),
        ];
    }
}
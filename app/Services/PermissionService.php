<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\NewRole;
use App\Models\NewUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Create a new permission
     */
    public function createPermission(array $data): Permission
    {
        DB::beginTransaction();
        
        try {
            $permission = Permission::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
                'scope' => $data['scope'] ?? Permission::SCOPE_GLOBAL,
                'module' => $data['module'],
                'action' => $data['action'],
                'resource' => $data['resource'] ?? null,
                'conditions' => $data['conditions'] ?? null,
                'is_system_permission' => $data['is_system_permission'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            DB::commit();
            
            // Clear permission cache
            $this->clearPermissionCache();
            
            Log::info('Permission created successfully', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'created_by' => auth()->id()
            ]);

            return $permission;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create permission', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update permission
     */
    public function updatePermission(Permission $permission, array $data): Permission
    {
        DB::beginTransaction();
        
        try {
            $permission->update([
                'display_name' => $data['display_name'] ?? $permission->display_name,
                'description' => $data['description'] ?? $permission->description,
                'scope' => $data['scope'] ?? $permission->scope,
                'module' => $data['module'] ?? $permission->module,
                'action' => $data['action'] ?? $permission->action,
                'resource' => $data['resource'] ?? $permission->resource,
                'conditions' => $data['conditions'] ?? $permission->conditions,
                'is_active' => $data['is_active'] ?? $permission->is_active,
                'sort_order' => $data['sort_order'] ?? $permission->sort_order,
            ]);

            DB::commit();
            
            // Clear permission cache
            $this->clearPermissionCache();
            
            Log::info('Permission updated successfully', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'updated_by' => auth()->id()
            ]);

            return $permission->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update permission', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Assign permission to role
     */
    public function assignPermissionToRole(NewRole $role, Permission $permission): bool
    {
        try {
            if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
                // Ensure the pivot sets is_granted so grantedPermissions queries work
                $role->permissions()->attach($permission->id, ['is_granted' => true]);
                
                // Clear permission cache
                $this->clearPermissionCache();
                
                Log::info('Permission assigned to role', [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'assigned_by' => auth()->id()
                ]);
                
                return true;
            }
            
            return false; // Already assigned
        } catch (\Exception $e) {
            Log::error('Failed to assign permission to role', [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove permission from role
     */
    public function removePermissionFromRole(NewRole $role, Permission $permission): bool
    {
        try {
            if ($role->permissions()->where('permission_id', $permission->id)->exists()) {
                $role->permissions()->detach($permission->id);
                
                // Clear permission cache
                $this->clearPermissionCache();
                
                Log::info('Permission removed from role', [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'removed_by' => auth()->id()
                ]);
                
                return true;
            }
            
            return false; // Not assigned
        } catch (\Exception $e) {
            Log::error('Failed to remove permission from role', [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if user has specific permission
     */
    public function userHasPermission(NewUser $user, string $permission, array $context = []): bool
    {
        // Super Admin has all permissions
        if ($user->hasRole(NewRole::SUPER_ADMIN)) {
            return true;
        }

        // Get user permissions from cache or database
        $userPermissions = $this->getUserPermissions($user);
        
        if (!in_array($permission, $userPermissions)) {
            return false;
        }

        // Check contextual permissions if provided
        if (!empty($context)) {
            return $this->checkContextualPermission($user, $permission, $context);
        }

        return true;
    }

    /**
     * Check contextual permission (e.g., school-specific, class-specific)
     */
    protected function checkContextualPermission(NewUser $user, string $permission, array $context): bool
    {
        // School context check
        if (isset($context['school_id'])) {
            $userSchools = $user->getAssignedSchools();
            if (!in_array($context['school_id'], $userSchools)) {
                return false;
            }
        }

        // Class context check for teachers
        if (isset($context['class_id']) && $user->hasRole(NewRole::TEACHER)) {
            $teacherAssignments = $user->teacherAssignments()
                ->where('is_active', true)
                ->where('class_id', $context['class_id'])
                ->exists();
            
            if (!$teacherAssignments) {
                return false;
            }
        }

        // Student context check for parents
        if (isset($context['student_id']) && $user->hasRole(NewRole::PARENT)) {
            $parentRelationship = $user->parentRelationships()
                ->where('student_id', $context['student_id'])
                ->where('is_active', true)
                ->exists();
            
            if (!$parentRelationship) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions(NewUser $user): array
    {
        $cacheKey = "user_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            // Directly granted permissions
            $directPermissions = $user->permissions()->pluck('name')->toArray();

            // Role-granted permissions via active roles
            $rolePermissions = $user->roles()
                ->with(['grantedPermissions' => function ($q) {
                    $q->select('permissions.id', 'permissions.name');
                }])
                ->get()
                ->flatMap(function ($role) {
                    return $role->grantedPermissions->pluck('name');
                })
                ->toArray();

            return array_values(array_unique(array_merge($directPermissions, $rolePermissions)));
        });
    }

    /**
     * Get permissions grouped by module
     */
    public function getPermissionsByModule(): array
    {
        $cacheKey = 'permissions_by_module';
        
        return Cache::remember($cacheKey, 3600, function () {
            $permissions = Permission::active()
                ->orderBy('module')
                ->orderBy('sort_order')
                ->get();
            
            $grouped = [];
            foreach ($permissions as $permission) {
                $grouped[$permission->module][] = [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'action' => $permission->action,
                    'resource' => $permission->resource,
                    'scope' => $permission->scope,
                ];
            }
            
            return $grouped;
        });
    }

    /**
     * Get role permissions matrix
     */
    public function getRolePermissionsMatrix(): array
    {
        $roles = NewRole::active()->orderBy('hierarchy_level')->get();
        $permissions = Permission::active()->orderBy('module')->orderBy('sort_order')->get();
        
        $matrix = [];
        
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions()->pluck('permission_id')->toArray();
            
            $matrix[$role->id] = [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'hierarchy_level' => $role->hierarchy_level,
                ],
                'permissions' => []
            ];
            
            foreach ($permissions as $permission) {
                $matrix[$role->id]['permissions'][$permission->id] = [
                    'permission' => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'module' => $permission->module,
                    ],
                    'has_permission' => in_array($permission->id, $rolePermissions)
                ];
            }
        }
        
        return $matrix;
    }

    /**
     * Bulk assign permissions to role
     */
    public function bulkAssignPermissionsToRole(NewRole $role, array $permissionIds): int
    {
        DB::beginTransaction();
        
        try {
            $validPermissions = Permission::whereIn('id', $permissionIds)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
            // Build sync payload with pivot values so they are marked granted
            $syncPayload = [];
            foreach ($validPermissions as $pid) {
                $syncPayload[$pid] = ['is_granted' => true];
            }

            $role->permissions()->sync($syncPayload);
            
            DB::commit();
            
            // Clear permission cache
            $this->clearPermissionCache();
            
            Log::info('Bulk permissions assigned to role', [
                'role_id' => $role->id,
                'permissions_count' => count($validPermissions),
                'assigned_by' => auth()->id()
            ]);
            
            return count($validPermissions);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk assign permissions to role', [
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create default system permissions
     */
    public function createDefaultPermissions(): int
    {
        $defaultPermissions = Permission::getDefaultSystemPermissions();
        $created = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($defaultPermissions as $permissionData) {
                $existing = Permission::where('name', $permissionData['name'])->first();
                
                if (!$existing) {
                    Permission::create($permissionData);
                    $created++;
                }
            }
            
            DB::commit();
            
            // Clear permission cache
            $this->clearPermissionCache();
            
            Log::info('Default permissions created', [
                'created_count' => $created,
                'total_permissions' => count($defaultPermissions)
            ]);
            
            return $created;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create default permissions', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate permission name format
     */
    public function validatePermissionName(string $name): bool
    {
        // Permission name should follow format: module.action or module.action.resource
        return preg_match('/^[a-z_]+\.[a-z_]+(\.[a-z_]+)?$/', $name);
    }

    /**
     * Generate permission name from components
     */
    public function generatePermissionName(string $module, string $action, string $resource = null): string
    {
        $name = strtolower($module) . '.' . strtolower($action);
        
        if ($resource) {
            $name .= '.' . strtolower($resource);
        }
        
        return $name;
    }

    /**
     * Clear permission cache
     */
    public function clearPermissionCache(): void
    {
        Cache::forget('permissions_by_module');
        
        // Clear user permission caches
        $users = NewUser::all();
        foreach ($users as $user) {
            Cache::forget("user_permissions_{$user->id}");
        }
        
        Log::info('Permission cache cleared');
    }

    /**
     * Get permission statistics
     */
    public function getPermissionStatistics(): array
    {
        return [
            'total_permissions' => Permission::count(),
            'active_permissions' => Permission::active()->count(),
            'system_permissions' => Permission::where('is_system_permission', true)->count(),
            'custom_permissions' => Permission::where('is_system_permission', false)->count(),
            'permissions_by_module' => Permission::active()
                ->groupBy('module')
                ->selectRaw('module, count(*) as count')
                ->pluck('count', 'module')
                ->toArray(),
            'permissions_by_scope' => Permission::active()
                ->groupBy('scope')
                ->selectRaw('scope, count(*) as count')
                ->pluck('count', 'scope')
                ->toArray(),
        ];
    }

    /**
     * Export permissions to array
     */
    public function exportPermissions(): array
    {
        return Permission::active()
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'module' => $permission->module,
                    'action' => $permission->action,
                    'resource' => $permission->resource,
                    'scope' => $permission->scope,
                    'conditions' => $permission->conditions,
                    'is_system_permission' => $permission->is_system_permission,
                    'sort_order' => $permission->sort_order,
                ];
            })
            ->toArray();
    }

    /**
     * Import permissions from array
     */
    public function importPermissions(array $permissions): int
    {
        $imported = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($permissions as $permissionData) {
                if (!$this->validatePermissionName($permissionData['name'])) {
                    continue;
                }
                
                $existing = Permission::where('name', $permissionData['name'])->first();
                
                if (!$existing) {
                    Permission::create($permissionData);
                    $imported++;
                } else {
                    $existing->update($permissionData);
                }
            }
            
            DB::commit();
            
            // Clear permission cache
            $this->clearPermissionCache();
            
            Log::info('Permissions imported', [
                'imported_count' => $imported,
                'total_permissions' => count($permissions)
            ]);
            
            return $imported;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import permissions', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
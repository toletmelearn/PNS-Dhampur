<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewRole extends Model
{
    use HasFactory;

    protected $table = 'new_roles';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'hierarchy_level',
        'permissions',
        'default_permissions',
        'can_create_users',
        'can_create_roles',
        'is_system_role',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'permissions' => 'array',
        'default_permissions' => 'array',
        'can_create_roles' => 'array',
        'can_create_users' => 'boolean',
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
        'hierarchy_level' => 'integer',
        'sort_order' => 'integer'
    ];

    // Role hierarchy constants
    const SUPER_ADMIN = 'super_admin';
    const ADMIN = 'admin';
    const PRINCIPAL = 'principal';
    const TEACHER = 'teacher';
    const STUDENT = 'student';
    const PARENT = 'parent';

    // Hierarchy levels
    const HIERARCHY_LEVELS = [
        self::SUPER_ADMIN => 1,
        self::ADMIN => 2,
        self::PRINCIPAL => 3,
        self::TEACHER => 4,
        self::STUDENT => 5,
        self::PARENT => 5
    ];

    // System roles that cannot be deleted
    const SYSTEM_ROLES = [
        self::SUPER_ADMIN,
        self::ADMIN,
        self::PRINCIPAL,
        self::TEACHER,
        self::STUDENT,
        self::PARENT
    ];

    /**
     * Get users assigned to this role
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role_assignments', 'role_id', 'user_id')
                    ->withPivot(['school_id', 'assigned_by', 'assigned_at', 'expires_at', 'is_active', 'is_primary', 'scope_restrictions', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get active users assigned to this role
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Get permissions associated with this role
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
                    ->withPivot(['is_granted', 'conditions'])
                    ->withTimestamps();
    }

    /**
     * Get granted permissions for this role
     */
    public function grantedPermissions(): BelongsToMany
    {
        return $this->permissions()->wherePivot('is_granted', true);
    }

    /**
     * Get role assignments
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class, 'role_id');
    }

    /**
     * Check if this role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Check in permissions array (legacy support)
        if (is_array($this->permissions) && in_array($permission, $this->permissions)) {
            return true;
        }

        // Check in role_permissions table
        return $this->grantedPermissions()->where('name', $permission)->exists();
    }

    /**
     * Check if this role has any of the specified permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if this role has all specified permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Grant a permission to this role
     */
    public function grantPermission(string $permissionName): bool
    {
        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            return false;
        }

        $this->permissions()->syncWithoutDetaching([
            $permission->id => ['is_granted' => true]
        ]);

        return true;
    }

    /**
     * Revoke a permission from this role
     */
    public function revokePermission(string $permissionName): bool
    {
        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            return false;
        }

        $this->permissions()->updateExistingPivot($permission->id, ['is_granted' => false]);
        return true;
    }

    /**
     * Get hierarchy level for this role
     */
    public function getHierarchyLevel(): int
    {
        return $this->hierarchy_level ?? self::HIERARCHY_LEVELS[$this->name] ?? 999;
    }

    /**
     * Check if this role can manage another role
     */
    public function canManageRole(NewRole $targetRole): bool
    {
        return $this->getHierarchyLevel() < $targetRole->getHierarchyLevel();
    }

    /**
     * Check if this role can create users
     */
    public function canCreateUsers(): bool
    {
        return $this->can_create_users;
    }

    /**
     * Check if this role can create a specific role
     */
    public function canCreateRole(string $roleName): bool
    {
        if (!$this->can_create_users) {
            return false;
        }

        $canCreateRoles = $this->can_create_roles ?? [];
        return in_array($roleName, $canCreateRoles) || in_array('*', $canCreateRoles);
    }

    /**
     * Get roles that this role can create
     */
    public function getCreatableRoles(): array
    {
        if (!$this->can_create_users) {
            return [];
        }

        $canCreateRoles = $this->can_create_roles ?? [];
        
        if (in_array('*', $canCreateRoles)) {
            // Can create all roles below in hierarchy
            return self::where('hierarchy_level', '>', $this->getHierarchyLevel())
                      ->where('is_active', true)
                      ->pluck('name')
                      ->toArray();
        }

        return $canCreateRoles;
    }

    /**
     * Check if role is system role
     */
    public function isSystemRole(): bool
    {
        return $this->is_system_role || in_array($this->name, self::SYSTEM_ROLES);
    }

    /**
     * Get default permissions for this role
     */
    public function getDefaultPermissions(): array
    {
        return $this->default_permissions ?? [];
    }

    /**
     * Apply default permissions to this role
     */
    public function applyDefaultPermissions(): void
    {
        $defaultPermissions = $this->getDefaultPermissions();
        
        foreach ($defaultPermissions as $permissionName) {
            $this->grantPermission($permissionName);
        }
    }

    /**
     * Get role display name
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?? ucwords(str_replace('_', ' ', $this->name));
    }

    /**
     * Scope: Active roles only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: System roles only
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    /**
     * Scope: Non-system roles only
     */
    public function scopeCustomRoles($query)
    {
        return $query->where('is_system_role', false);
    }

    /**
     * Scope: Roles by hierarchy level
     */
    public function scopeByHierarchyLevel($query, int $level)
    {
        return $query->where('hierarchy_level', $level);
    }

    /**
     * Scope: Roles above hierarchy level
     */
    public function scopeAboveHierarchyLevel($query, int $level)
    {
        return $query->where('hierarchy_level', '<', $level);
    }

    /**
     * Scope: Roles below hierarchy level
     */
    public function scopeBelowHierarchyLevel($query, int $level)
    {
        return $query->where('hierarchy_level', '>', $level);
    }

    /**
     * Get role statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => $this->users()->count(),
            'active_users' => $this->activeUsers()->count(),
            'total_permissions' => $this->permissions()->count(),
            'granted_permissions' => $this->grantedPermissions()->count(),
            'hierarchy_level' => $this->getHierarchyLevel(),
            'is_system_role' => $this->isSystemRole(),
            'can_create_users' => $this->canCreateUsers(),
            'creatable_roles' => $this->getCreatableRoles()
        ];
    }
}
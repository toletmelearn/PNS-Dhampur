<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action',
        'scope',
        'is_system_permission',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_system_permission' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Permission scopes
    const SCOPE_OWN = 'own';           // User's own data only
    const SCOPE_ASSIGNED = 'assigned'; // Data assigned to user (e.g., teacher's classes)
    const SCOPE_SCHOOL = 'school';     // School-wide data
    const SCOPE_ALL = 'all';           // All data across system

    // Common modules
    const MODULE_USERS = 'users';
    const MODULE_ROLES = 'roles';
    const MODULE_SCHOOLS = 'schools';
    const MODULE_ATTENDANCE = 'attendance';
    const MODULE_GRADES = 'grades';
    const MODULE_REPORTS = 'reports';
    const MODULE_SETTINGS = 'settings';
    const MODULE_SYSTEM = 'system';

    // Common actions
    const ACTION_VIEW = 'view';
    const ACTION_CREATE = 'create';
    const ACTION_EDIT = 'edit';
    const ACTION_DELETE = 'delete';
    const ACTION_MANAGE = 'manage';
    const ACTION_EXPORT = 'export';
    const ACTION_IMPORT = 'import';

    /**
     * Get roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(NewRole::class, 'role_permissions', 'permission_id', 'role_id')
                    ->withPivot(['is_granted', 'conditions'])
                    ->withTimestamps();
    }

    /**
     * Get roles that have this permission granted
     */
    public function grantedRoles(): BelongsToMany
    {
        return $this->roles()->wherePivot('is_granted', true);
    }

    /**
     * Get users that have this permission directly assigned
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                    ->withPivot(['is_granted', 'granted_by', 'granted_at', 'expires_at', 'reason'])
                    ->withTimestamps();
    }

    /**
     * Get users that have this permission granted directly
     */
    public function grantedUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_granted', true);
    }

    /**
     * Get the full permission name (module.action.scope)
     */
    public function getFullName(): string
    {
        $parts = [$this->module, $this->action];
        
        if ($this->scope && $this->scope !== self::SCOPE_OWN) {
            $parts[] = $this->scope;
        }
        
        return implode('.', $parts);
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?? $this->generateDisplayName();
    }

    /**
     * Generate display name from components
     */
    protected function generateDisplayName(): string
    {
        $action = ucfirst($this->action);
        $module = ucfirst($this->module);
        $scope = $this->scope ? " ({$this->scope})" : '';
        
        return "{$action} {$module}{$scope}";
    }

    /**
     * Check if this is a system permission
     */
    public function isSystemPermission(): bool
    {
        return $this->is_system_permission;
    }

    /**
     * Check if permission applies to specific scope
     */
    public function hasScope(string $scope): bool
    {
        return $this->scope === $scope;
    }

    /**
     * Check if permission is for specific module
     */
    public function isForModule(string $module): bool
    {
        return $this->module === $module;
    }

    /**
     * Check if permission is for specific action
     */
    public function isForAction(string $action): bool
    {
        return $this->action === $action;
    }

    /**
     * Scope: Active permissions only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: System permissions only
     */
    public function scopeSystemPermissions($query)
    {
        return $query->where('is_system_permission', true);
    }

    /**
     * Scope: Custom permissions only
     */
    public function scopeCustomPermissions($query)
    {
        return $query->where('is_system_permission', false);
    }

    /**
     * Scope: Permissions by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Permissions by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Permissions by scope
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Create permission from string
     */
    public static function createFromString(string $permissionString, array $attributes = []): self
    {
        $parts = explode('.', $permissionString);
        
        $module = $parts[0] ?? 'general';
        $action = $parts[1] ?? 'view';
        $scope = $parts[2] ?? self::SCOPE_OWN;
        
        return self::create(array_merge([
            'name' => $permissionString,
            'module' => $module,
            'action' => $action,
            'scope' => $scope,
            'display_name' => ucfirst($action) . ' ' . ucfirst($module) . ($scope !== self::SCOPE_OWN ? " ({$scope})" : ''),
            'is_active' => true,
            'is_system_permission' => false
        ], $attributes));
    }

    /**
     * Get all permissions grouped by module
     */
    public static function getGroupedByModule(): array
    {
        return self::active()
                  ->orderBy('module')
                  ->orderBy('sort_order')
                  ->orderBy('name')
                  ->get()
                  ->groupBy('module')
                  ->toArray();
    }

    /**
     * Get permissions for a specific role
     */
    public static function getForRole(string $roleName): array
    {
        $role = NewRole::where('name', $roleName)->first();
        if (!$role) {
            return [];
        }

        return $role->grantedPermissions()->pluck('name')->toArray();
    }

    /**
     * Get system permissions that should be created by default
     */
    public static function getSystemPermissions(): array
    {
        return [
            // User Management
            'users.view.own' => ['display_name' => 'View Own Profile', 'module' => 'users', 'action' => 'view', 'scope' => 'own'],
            'users.edit.own' => ['display_name' => 'Edit Own Profile', 'module' => 'users', 'action' => 'edit', 'scope' => 'own'],
            'users.view.assigned' => ['display_name' => 'View Assigned Users', 'module' => 'users', 'action' => 'view', 'scope' => 'assigned'],
            'users.view.school' => ['display_name' => 'View School Users', 'module' => 'users', 'action' => 'view', 'scope' => 'school'],
            'users.view.all' => ['display_name' => 'View All Users', 'module' => 'users', 'action' => 'view', 'scope' => 'all'],
            'users.create' => ['display_name' => 'Create Users', 'module' => 'users', 'action' => 'create', 'scope' => 'all'],
            'users.edit.assigned' => ['display_name' => 'Edit Assigned Users', 'module' => 'users', 'action' => 'edit', 'scope' => 'assigned'],
            'users.edit.school' => ['display_name' => 'Edit School Users', 'module' => 'users', 'action' => 'edit', 'scope' => 'school'],
            'users.edit.all' => ['display_name' => 'Edit All Users', 'module' => 'users', 'action' => 'edit', 'scope' => 'all'],
            'users.delete.school' => ['display_name' => 'Delete School Users', 'module' => 'users', 'action' => 'delete', 'scope' => 'school'],
            'users.delete.all' => ['display_name' => 'Delete All Users', 'module' => 'users', 'action' => 'delete', 'scope' => 'all'],

            // Role Management
            'roles.view' => ['display_name' => 'View Roles', 'module' => 'roles', 'action' => 'view', 'scope' => 'all'],
            'roles.create' => ['display_name' => 'Create Roles', 'module' => 'roles', 'action' => 'create', 'scope' => 'all'],
            'roles.edit' => ['display_name' => 'Edit Roles', 'module' => 'roles', 'action' => 'edit', 'scope' => 'all'],
            'roles.delete' => ['display_name' => 'Delete Roles', 'module' => 'roles', 'action' => 'delete', 'scope' => 'all'],
            'roles.assign' => ['display_name' => 'Assign Roles', 'module' => 'roles', 'action' => 'assign', 'scope' => 'all'],

            // School Management
            'schools.view.own' => ['display_name' => 'View Own School', 'module' => 'schools', 'action' => 'view', 'scope' => 'own'],
            'schools.view.all' => ['display_name' => 'View All Schools', 'module' => 'schools', 'action' => 'view', 'scope' => 'all'],
            'schools.create' => ['display_name' => 'Create Schools', 'module' => 'schools', 'action' => 'create', 'scope' => 'all'],
            'schools.edit.own' => ['display_name' => 'Edit Own School', 'module' => 'schools', 'action' => 'edit', 'scope' => 'own'],
            'schools.edit.all' => ['display_name' => 'Edit All Schools', 'module' => 'schools', 'action' => 'edit', 'scope' => 'all'],
            'schools.delete' => ['display_name' => 'Delete Schools', 'module' => 'schools', 'action' => 'delete', 'scope' => 'all'],

            // Attendance Management
            'attendance.view.own' => ['display_name' => 'View Own Attendance', 'module' => 'attendance', 'action' => 'view', 'scope' => 'own'],
            'attendance.view.assigned' => ['display_name' => 'View Assigned Attendance', 'module' => 'attendance', 'action' => 'view', 'scope' => 'assigned'],
            'attendance.view.school' => ['display_name' => 'View School Attendance', 'module' => 'attendance', 'action' => 'view', 'scope' => 'school'],
            'attendance.view.all' => ['display_name' => 'View All Attendance', 'module' => 'attendance', 'action' => 'view', 'scope' => 'all'],
            'attendance.mark.assigned' => ['display_name' => 'Mark Assigned Attendance', 'module' => 'attendance', 'action' => 'mark', 'scope' => 'assigned'],
            'attendance.mark.school' => ['display_name' => 'Mark School Attendance', 'module' => 'attendance', 'action' => 'mark', 'scope' => 'school'],
            'attendance.mark.all' => ['display_name' => 'Mark All Attendance', 'module' => 'attendance', 'action' => 'mark', 'scope' => 'all'],
            'attendance.edit.assigned' => ['display_name' => 'Edit Assigned Attendance', 'module' => 'attendance', 'action' => 'edit', 'scope' => 'assigned'],
            'attendance.edit.school' => ['display_name' => 'Edit School Attendance', 'module' => 'attendance', 'action' => 'edit', 'scope' => 'school'],
            'attendance.edit.all' => ['display_name' => 'Edit All Attendance', 'module' => 'attendance', 'action' => 'edit', 'scope' => 'all'],

            // Reports
            'reports.view.own' => ['display_name' => 'View Own Reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'own'],
            'reports.view.assigned' => ['display_name' => 'View Assigned Reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'assigned'],
            'reports.view.school' => ['display_name' => 'View School Reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'school'],
            'reports.view.all' => ['display_name' => 'View All Reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'all'],
            'reports.export.assigned' => ['display_name' => 'Export Assigned Reports', 'module' => 'reports', 'action' => 'export', 'scope' => 'assigned'],
            'reports.export.school' => ['display_name' => 'Export School Reports', 'module' => 'reports', 'action' => 'export', 'scope' => 'school'],
            'reports.export.all' => ['display_name' => 'Export All Reports', 'module' => 'reports', 'action' => 'export', 'scope' => 'all'],

            // System Settings
            'settings.view' => ['display_name' => 'View Settings', 'module' => 'settings', 'action' => 'view', 'scope' => 'all'],
            'settings.edit' => ['display_name' => 'Edit Settings', 'module' => 'settings', 'action' => 'edit', 'scope' => 'all'],
            'system.backup' => ['display_name' => 'System Backup', 'module' => 'system', 'action' => 'backup', 'scope' => 'all'],
            'system.maintenance' => ['display_name' => 'System Maintenance', 'module' => 'system', 'action' => 'maintenance', 'scope' => 'all'],
        ];
    }
}
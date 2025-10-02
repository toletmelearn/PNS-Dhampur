<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return in_array($this->role, $roles);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission)
    {
        return Role::hasPermission($this->role, $permission);
    }

    /**
     * Get user's role level
     */
    public function getRoleLevel()
    {
        return Role::getRoleLevel($this->role);
    }

    /**
     * Check if user can access attendance module
     */
    public function canAccessAttendance()
    {
        return Role::canAccessAttendance($this->role);
    }

    /**
     * Get user-friendly role name
     */
    public function getRoleName()
    {
        return Role::getRoleName($this->role);
    }

    /**
     * Get role description
     */
    public function getRoleDescription()
    {
        return Role::getRoleDescription($this->role);
    }

    /**
     * Get allowed navigation items
     */
    public function getAllowedNavigation()
    {
        return Role::getAllowedNavigation($this->role);
    }

    /**
     * Check if user is admin (including principal and IT)
     */
    public function isAdmin()
    {
        return $this->hasAnyRole([Role::ADMIN, 'principal', 'it']);
    }

    /**
     * Check if user is teacher (including class teacher and exam incharge)
     */
    public function isTeacher()
    {
        return $this->hasAnyRole([Role::TEACHER, 'class_teacher', 'exam_incharge']);
    }

    /**
     * Check if user is student
     */
    public function isStudent()
    {
        return $this->hasRole(Role::STUDENT);
    }

    /**
     * Check if user can manage other users
     */
    public function canManageUsers()
    {
        return $this->hasPermission('attendance.manage_users');
    }

    /**
     * Check if user can view all attendance records
     */
    public function canViewAllAttendance()
    {
        return $this->hasPermission('attendance.view_all');
    }

    /**
     * Check if user can mark attendance for others
     */
    public function canMarkAttendance()
    {
        return $this->hasAnyPermission([
            'attendance.mark_all',
            'attendance.mark_assigned'
        ]);
    }

    /**
     * Check if user has any of the specified permissions
     */
    public function hasAnyPermission($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user's attendance permissions
     */
    public function getAttendancePermissions()
    {
        return Role::getAttendancePermissions($this->role);
    }

    /**
     * Scope to filter users by role
     */
    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to filter users by multiple roles
     */
    public function scopeWithAnyRole($query, $roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return $query->whereIn('role', $roles);
    }

    /**
     * Scope to get admin users
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [Role::ADMIN, 'principal', 'it']);
    }

    /**
     * Scope to get teacher users
     */
    public function scopeTeachers($query)
    {
        return $query->whereIn('role', [Role::TEACHER, 'class_teacher', 'exam_incharge']);
    }

    /**
     * Scope to get student users
     */
    public function scopeStudents($query)
    {
        return $query->where('role', Role::STUDENT);
    }
}

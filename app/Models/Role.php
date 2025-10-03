<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    // Define the three main roles for attendance system
    const ADMIN = 'admin';
    const TEACHER = 'teacher';
    const STUDENT = 'student';

    // All available roles (including legacy ones)
    const ALL_ROLES = [
        self::ADMIN,
        'principal',
        self::TEACHER,
        'accountant',
        self::STUDENT,
        'it',
        'exam_incharge',
        'class_teacher'
    ];

    // Main attendance system roles
    const ATTENDANCE_ROLES = [
        self::ADMIN,
        self::TEACHER,
        self::STUDENT
    ];

    /**
     * Get role permissions for attendance system
     */
    public static function getAttendancePermissions($role)
    {
        $permissions = [
            self::ADMIN => [
                // Full system access
                'attendance.view_all',
                'attendance.mark_all',
                'attendance.edit_all',
                'attendance.delete_all',
                'attendance.reports_all',
                'attendance.analytics_all',
                'attendance.bulk_operations',
                'attendance.export_data',
                'attendance.manage_settings',
                'attendance.manage_users',
                'attendance.audit_logs',
                
                // Class Data Audit System permissions
                'view-class-audit',
                'view_audit_trails',
                'manage_audit_rollbacks',
                'view_audit_approvals',
                'approve_audit_changes',
                'delegate_audit_approvals',
                'bulk_approve_audits',
                'view_audit_statistics',
                'export_audit_reports',
                
                // Student management
                'students.view_all',
                'students.create',
                'students.edit_all',
                'students.delete_all',
                
                // Teacher management
                'teachers.view_all',
                'teachers.create',
                'teachers.edit_all',
                'teachers.delete_all',
                
                // System settings
                'system.configure',
                'system.backup',
                'system.maintenance'
            ],
            
            self::TEACHER => [
                // Attendance management for assigned classes
                'attendance.view_assigned',
                'attendance.mark_assigned',
                'attendance.edit_assigned',
                'attendance.reports_assigned',
                'attendance.analytics_assigned',
                'attendance.bulk_mark_assigned',
                'attendance.export_assigned',
                
                // Class Data Audit System permissions (limited for teachers)
                'view-class-audit',
                'view_audit_trails',
                'view_audit_approvals',
                
                // Student management for assigned classes
                'students.view_assigned',
                'students.edit_basic_info',
                
                // Own profile management
                'profile.view_own',
                'profile.edit_own',
                
                // Reports and analytics
                'reports.generate_class',
                'analytics.view_class'
            ],
            
            self::STUDENT => [
                // View own attendance only
                'attendance.view_own',
                'attendance.reports_own',
                
                // Own profile management
                'profile.view_own',
                'profile.edit_basic',
                
                // View own academic information
                'academic.view_own',
                'reports.view_own'
            ]
        ];

        // Handle legacy roles by mapping them to main roles
        $roleMapping = [
            'principal' => self::ADMIN,
            'class_teacher' => self::TEACHER,
            'accountant' => self::ADMIN, // Limited admin access
            'it' => self::ADMIN,
            'exam_incharge' => self::TEACHER
        ];

        $mappedRole = $roleMapping[$role] ?? $role;
        
        return $permissions[$mappedRole] ?? [];
    }

    /**
     * Check if user has specific permission
     */
    public static function hasPermission($userRole, $permission)
    {
        $permissions = self::getAttendancePermissions($userRole);
        return in_array($permission, $permissions);
    }

    /**
     * Get role hierarchy level (higher number = more permissions)
     */
    public static function getRoleLevel($role)
    {
        $levels = [
            self::STUDENT => 1,
            self::TEACHER => 2,
            'class_teacher' => 2,
            'exam_incharge' => 2,
            'accountant' => 3,
            'it' => 4,
            'principal' => 5,
            self::ADMIN => 5
        ];

        return $levels[$role] ?? 0;
    }

    /**
     * Check if role can access attendance module
     */
    public static function canAccessAttendance($role)
    {
        return in_array($role, self::ALL_ROLES);
    }

    /**
     * Get user-friendly role name
     */
    public static function getRoleName($role)
    {
        $names = [
            self::ADMIN => 'Administrator',
            'principal' => 'Principal',
            self::TEACHER => 'Teacher',
            'class_teacher' => 'Class Teacher',
            'accountant' => 'Accountant',
            self::STUDENT => 'Student',
            'it' => 'IT Administrator',
            'exam_incharge' => 'Exam In-charge'
        ];

        return $names[$role] ?? ucfirst($role);
    }

    /**
     * Get role description
     */
    public static function getRoleDescription($role)
    {
        $descriptions = [
            self::ADMIN => 'Full system access with all administrative privileges',
            'principal' => 'School principal with administrative oversight',
            self::TEACHER => 'Teacher with access to assigned classes and students',
            'class_teacher' => 'Class teacher with additional class management responsibilities',
            'accountant' => 'Financial administrator with limited system access',
            self::STUDENT => 'Student with access to personal attendance and academic information',
            'it' => 'IT administrator with technical system access',
            'exam_incharge' => 'Examination coordinator with exam-related privileges'
        ];

        return $descriptions[$role] ?? 'Standard user role';
    }

    /**
     * Get allowed navigation items for role
     */
    public static function getAllowedNavigation($role)
    {
        $navigation = [
            self::ADMIN => [
                'dashboard',
                'attendance',
                'students',
                'teachers',
                'reports',
                'analytics',
                'settings',
                'users',
                'audit-logs'
            ],
            
            self::TEACHER => [
                'dashboard',
                'attendance',
                'students',
                'reports',
                'analytics',
                'profile'
            ],
            
            self::STUDENT => [
                'dashboard',
                'attendance',
                'profile',
                'reports'
            ]
        ];

        // Map legacy roles
        $roleMapping = [
            'principal' => self::ADMIN,
            'class_teacher' => self::TEACHER,
            'accountant' => ['dashboard', 'reports', 'analytics', 'profile'],
            'it' => self::ADMIN,
            'exam_incharge' => self::TEACHER
        ];

        if (isset($roleMapping[$role])) {
            if (is_array($roleMapping[$role])) {
                return $roleMapping[$role];
            }
            return $navigation[$roleMapping[$role]] ?? [];
        }

        return $navigation[$role] ?? [];
    }
}
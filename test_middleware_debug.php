<?php

// Simple test to understand the middleware behavior
echo "Testing middleware logic:\n\n";

// Simulate the role check logic from RoleMiddleware
function hasAnyRole($userRole, $requiredRoles) {
    return in_array($userRole, $requiredRoles);
}

// Test the role check
$userRole = 'admin';
$requiredRoles = ['admin', 'principal', 'class_teacher'];

echo "User role: $userRole\n";
echo "Required roles: " . implode(', ', $requiredRoles) . "\n";
echo "Has any role: " . (hasAnyRole($userRole, $requiredRoles) ? 'Yes' : 'No') . "\n\n";

// Test the ALL_ROLES check (from Role model)
$allRoles = [
    'admin',
    'principal', 
    'teacher',
    'accountant',
    'student',
    'it',
    'exam_incharge',
    'class_teacher'
];

echo "Can access attendance (admin in ALL_ROLES): " . (in_array('admin', $allRoles) ? 'Yes' : 'No') . "\n";

// Test permission check logic
$adminPermissions = [
    'attendance.view_all', 'attendance.mark_all', 'attendance.edit_all', 'attendance.delete_all',
    'attendance.reports_all', 'attendance.analytics_all', 'attendance.bulk_operations', 
    'attendance.export_data', 'attendance.manage_settings', 'attendance.manage_approvals',
    'view_audit_trails', 'manage_audit_rollbacks', 'view_audit_approvals', 'approve_audit_changes',
    'delegate_audit_approvals', 'bulk_approve_audits', 'view_audit_statistics', 'export_audit_reports',
    'students.view_all', 'students.create', 'students.edit_all', 'students.delete_all',
    'teachers.view_all', 'teachers.create', 'teachers.edit_all', 'teachers.delete_all',
    'system.configure', 'system.backup', 'system.maintenance'
];

echo "Has view_audit_trails permission: " . (in_array('view_audit_trails', $adminPermissions) ? 'Yes' : 'No') . "\n";
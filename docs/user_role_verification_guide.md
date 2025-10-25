# User Role Verification Guide

This guide provides practical steps to verify that each user role has the proper access levels as defined in the requirements.

## Quick Verification Steps

### 1. Manual Testing Checklist

For each role, log in as a test user and verify access to these key features:

#### Super Admin
- [ ] Access system configuration page
- [ ] Create a new user with any role
- [ ] Access reports across multiple schools

#### Admin
- [ ] Create a new school
- [ ] Manage financial settings
- [ ] Generate school-level reports

#### Principal
- [ ] Manage teachers in their school
- [ ] Configure academic calendar
- [ ] Access school-specific reports

#### Teachers
- [ ] Manage class and subject content
- [ ] Enter attendance and grades
- [ ] Upload syllabus and exam papers

#### Students
- [ ] View personal academic data
- [ ] Access syllabus and view results
- [ ] Make fee payments

#### Parents
- [ ] Monitor child's progress
- [ ] Make fee payments
- [ ] View attendance and grades

### 2. Route Access Verification

Test these key routes for each role to ensure proper access control:

```php
// Super Admin routes
/admin/system/settings
/admin/users
/admin/reports/system

// Admin routes
/admin/school
/admin/users/create
/admin/finance/dashboard

// Principal routes
/school/dashboard
/school/teachers
/school/calendar

// Teacher routes
/teacher/classes
/teacher/attendance
/teacher/syllabus

// Student routes
/student/profile
/student/syllabus
/student/fees

// Parent routes
/parent/children/progress
/parent/fees
/parent/attendance
```

## Implementation Recommendations

1. **Use middleware consistently** on all routes
2. **Implement view-based permission checks** for UI elements
3. **Add role assertions** in controller methods
4. **Document any deviations** from the standard permission model

## Common Issues and Solutions

1. **Issue**: Higher-level roles cannot access lower-level features
   **Solution**: Verify role hierarchy implementation in middleware

2. **Issue**: Users can see UI elements they cannot access
   **Solution**: Add permission checks in Blade templates

3. **Issue**: API endpoints bypass permission checks
   **Solution**: Apply consistent middleware to API routes

4. **Issue**: Custom permissions not working
   **Solution**: Check permission registration in service providers

## Next Steps

1. Implement automated testing for role permissions
2. Create a permission audit report
3. Document any custom permissions added to the system
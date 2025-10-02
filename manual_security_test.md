# Manual Security Testing Guide

## Test User Credentials

### Admin Users
- **Super Admin**: admin@pnsdhampur.local / Password123
- **Admin User**: admin@pns-dhampur.edu / password

### Teacher Users  
- **Test Teacher**: test@teacher.com / password123
- **Teacher 1**: teacher1@pns-dhampur.edu / password
- **Teacher 2**: teacher2@pns-dhampur.edu / password
- (Additional teachers: teacher3@pns-dhampur.edu to teacher10@pns-dhampur.edu / password)

### Student Users
- **Student One**: student1@pns-dhampur.edu / password123
- **Student Two**: student2@pns-dhampur.edu / password123

### Principal User
- **Principal**: principal@pns-dhampur.edu / password123

## Manual Testing Checklist

### 1. Role-Based Access Control Testing

#### Admin Access Testing
- [ ] Login as admin@pnsdhampur.local
- [ ] Verify access to admin dashboard
- [ ] Test access to user management features
- [ ] Test access to system settings
- [ ] Test bulk operations permissions
- [ ] Verify can view all attendance records

#### Teacher Access Testing  
- [ ] Login as test@teacher.com
- [ ] Verify redirected to appropriate teacher dashboard
- [ ] Test access to assigned class attendance
- [ ] Verify cannot access admin-only routes (/admin/users)
- [ ] Test cannot perform bulk operations without permission
- [ ] Verify can only edit assigned class records

#### Student Access Testing
- [ ] Login as student1@pns-dhampur.edu  
- [ ] Verify redirected to student dashboard
- [ ] Test can only view own attendance records
- [ ] Verify cannot access teacher/admin routes
- [ ] Test cannot mark attendance for others
- [ ] Verify 403 errors for unauthorized access

#### Principal Access Testing
- [ ] Login as principal@pns-dhampur.edu
- [ ] Verify has admin-level permissions
- [ ] Test access to all system features
- [ ] Verify can view all reports and data

### 2. CSRF Protection Testing

#### Form-Based CSRF Testing
- [ ] Login to any account
- [ ] Inspect login form for CSRF token (_token field)
- [ ] Inspect registration form for CSRF token
- [ ] Test form submission without CSRF token (should fail)
- [ ] Test form submission with invalid CSRF token (should fail)
- [ ] Test form submission with valid CSRF token (should succeed)

#### API Endpoint CSRF Testing
- [ ] Test POST requests to /logout without CSRF token
- [ ] Test POST requests to attendance endpoints without CSRF token
- [ ] Verify 419 or appropriate error responses for missing CSRF tokens

### 3. Session Security Testing

#### Session Management
- [ ] Login and verify session is created
- [ ] Test session timeout (default: 2 hours)
- [ ] Verify session invalidation on logout
- [ ] Test concurrent sessions from different browsers
- [ ] Verify session data is properly encrypted

#### Authentication Flow
- [ ] Test login with valid credentials
- [ ] Test login with invalid credentials
- [ ] Test password reset functionality
- [ ] Verify proper redirects after authentication
- [ ] Test "remember me" functionality

### 4. Route Protection Testing

#### Protected Routes
- [ ] Access /dashboard without authentication (should redirect to login)
- [ ] Access /admin/* routes as non-admin (should return 403)
- [ ] Access teacher-only routes as student (should return 403)
- [ ] Test middleware protection on API endpoints

#### Public Routes
- [ ] Verify /login is accessible without authentication
- [ ] Verify /register is accessible without authentication
- [ ] Test static assets are accessible

### 5. Error Handling Testing

#### Security Error Responses
- [ ] Test 403 Forbidden responses for unauthorized access
- [ ] Test 419 CSRF token mismatch responses
- [ ] Test 401 Unauthorized responses for unauthenticated access
- [ ] Verify error messages don't leak sensitive information

## Testing URLs

- **Login Page**: http://127.0.0.1:8000/login
- **Registration**: http://127.0.0.1:8000/register  
- **Dashboard**: http://127.0.0.1:8000/dashboard
- **Admin Panel**: http://127.0.0.1:8000/admin
- **User Management**: http://127.0.0.1:8000/admin/users
- **Attendance**: http://127.0.0.1:8000/attendance
- **Bulk Attendance**: http://127.0.0.1:8000/bulk-attendance

## Expected Security Behaviors

1. **Unauthorized Access**: Should return 403 Forbidden
2. **Unauthenticated Access**: Should redirect to login with 302
3. **CSRF Protection**: Should return 419 for missing/invalid tokens
4. **Session Timeout**: Should redirect to login after 2 hours
5. **Role Validation**: Should enforce role-based permissions strictly

## Notes

- All passwords are for testing purposes only
- Test in incognito/private browsing mode for clean sessions
- Clear browser cache between different role tests
- Monitor Laravel logs for security-related errors
- Verify HTTPS in production environments
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class SecurityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for testing
        $this->artisan('migrate:fresh');
        
        // Create test users with different roles
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'password' => bcrypt('password')
        ]);

        $this->teacherUser = User::factory()->create([
            'name' => 'Teacher User',
            'email' => 'teacher@test.com',
            'role' => 'teacher',
            'password' => bcrypt('password')
        ]);

        $this->studentUser = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@test.com',
            'role' => 'student',
            'password' => bcrypt('password')
        ]);

        $this->principalUser = User::factory()->create([
            'name' => 'Principal User',
            'email' => 'principal@test.com',
            'role' => 'principal',
            'password' => bcrypt('password')
        ]);
    }

    /** @test */
    public function test_unauthenticated_users_cannot_access_protected_routes()
    {
        // Test attendance routes
        $protectedRoutes = [
            '/biometric-attendance',
            '/student-attendance',
            '/attendance-reports',
            '/bulk-attendance'
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /** @test */
    public function test_admin_has_full_access_to_attendance_system()
    {
        $this->actingAs($this->adminUser);

        // Test admin can access all attendance routes
        $adminRoutes = [
            '/biometric-attendance',
            '/student-attendance',
            '/attendance-reports'
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function test_teacher_has_appropriate_attendance_permissions()
    {
        $this->actingAs($this->teacherUser);

        // Teachers should be able to mark attendance
        $response = $this->get('/biometric-attendance');
        $response->assertStatus(200);

        // Teachers should be able to view student attendance
        $response = $this->get('/student-attendance');
        $response->assertStatus(200);

        // Teachers should be able to view reports
        $response = $this->get('/attendance-reports');
        $response->assertStatus(200);
    }

    /** @test */
    public function test_student_has_limited_attendance_access()
    {
        $this->actingAs($this->studentUser);

        // Students should only be able to view their own attendance
        $response = $this->get('/student-attendance');
        $response->assertStatus(200);

        // Students should NOT be able to mark attendance for others
        $response = $this->get('/biometric-attendance');
        $response->assertStatus(403);
    }

    /** @test */
    public function test_role_middleware_blocks_unauthorized_access()
    {
        // Test student trying to access admin-only routes
        $this->actingAs($this->studentUser);
        
        $response = $this->get('/bulk-attendance');
        $response->assertStatus(403);

        // Test teacher trying to access admin-only routes
        $this->actingAs($this->teacherUser);
        
        $response = $this->get('/admin/users');
        $response->assertStatus(403);
    }

    /** @test */
    public function test_permission_middleware_validates_specific_permissions()
    {
        // Test user without bulk operations permission
        $this->actingAs($this->studentUser);
        
        $response = $this->post('/attendance/bulk-mark', [
            '_token' => csrf_token(),
            'attendance_data' => []
        ]);
        $response->assertStatus(403);

        // Test user with bulk operations permission
        $this->actingAs($this->adminUser);
        
        $response = $this->post('/attendance/bulk-mark', [
            '_token' => csrf_token(),
            'attendance_data' => []
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function test_csrf_protection_is_enforced()
    {
        $this->actingAs($this->adminUser);

        // Test POST request without CSRF token
        $response = $this->post('/attendance/mark', [
            'student_id' => 1,
            'status' => 'present'
        ]);
        $response->assertStatus(419); // CSRF token mismatch

        // Test POST request with valid CSRF token
        $response = $this->post('/attendance/mark', [
            '_token' => csrf_token(),
            'student_id' => 1,
            'status' => 'present'
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function test_session_security_configuration()
    {
        // Test session lifetime is properly configured
        $this->assertEquals(60, config('session.lifetime'));
        
        // Test session expires on browser close
        $this->assertTrue(config('session.expire_on_close'));
        
        // Test session encryption is enabled
        $this->assertTrue(config('session.encrypt'));
        
        // Test secure cookies in production
        if (app()->environment('production')) {
            $this->assertTrue(config('session.secure'));
        }
        
        // Test HTTP-only cookies
        $this->assertTrue(config('session.http_only'));
        
        // Test same-site cookie policy
        $this->assertEquals('strict', config('session.same_site'));
    }

    /** @test */
    public function test_attendance_security_middleware_detects_suspicious_activity()
    {
        $this->actingAs($this->adminUser);

        // Simulate rapid requests (potential attack)
        for ($i = 0; $i < 15; $i++) {
            $response = $this->get('/biometric-attendance');
        }
        
        // Should trigger rate limiting
        $response = $this->get('/biometric-attendance');
        $response->assertStatus(429);
    }

    /** @test */
    public function test_role_based_navigation_access()
    {
        // Test admin navigation
        $this->actingAs($this->adminUser);
        $navigation = $this->adminUser->getAllowedNavigation();
        $this->assertContains('dashboard', $navigation);
        $this->assertContains('attendance', $navigation);
        $this->assertContains('users', $navigation);
        $this->assertContains('reports', $navigation);

        // Test teacher navigation
        $this->actingAs($this->teacherUser);
        $navigation = $this->teacherUser->getAllowedNavigation();
        $this->assertContains('dashboard', $navigation);
        $this->assertContains('attendance', $navigation);
        $this->assertNotContains('users', $navigation);

        // Test student navigation
        $this->actingAs($this->studentUser);
        $navigation = $this->studentUser->getAllowedNavigation();
        $this->assertContains('dashboard', $navigation);
        $this->assertContains('attendance', $navigation);
        $this->assertNotContains('users', $navigation);
    }

    /** @test */
    public function test_user_role_validation_methods()
    {
        // Test admin role validation
        $this->assertTrue($this->adminUser->hasRole('admin'));
        $this->assertTrue($this->adminUser->isAdmin());
        $this->assertFalse($this->adminUser->isTeacher());
        $this->assertFalse($this->adminUser->isStudent());

        // Test teacher role validation
        $this->assertTrue($this->teacherUser->hasRole('teacher'));
        $this->assertTrue($this->teacherUser->isTeacher());
        $this->assertFalse($this->teacherUser->isAdmin());
        $this->assertFalse($this->teacherUser->isStudent());

        // Test student role validation
        $this->assertTrue($this->studentUser->hasRole('student'));
        $this->assertTrue($this->studentUser->isStudent());
        $this->assertFalse($this->studentUser->isAdmin());
        $this->assertFalse($this->studentUser->isTeacher());
    }

    /** @test */
    public function test_attendance_permissions_by_role()
    {
        // Test admin permissions
        $adminPermissions = $this->adminUser->getAttendancePermissions();
        $this->assertContains('attendance.view_all', $adminPermissions);
        $this->assertContains('attendance.mark_all', $adminPermissions);
        $this->assertContains('attendance.edit_all', $adminPermissions);
        $this->assertContains('attendance.delete_all', $adminPermissions);
        $this->assertContains('attendance.bulk_operations', $adminPermissions);
        $this->assertContains('attendance.reports_all', $adminPermissions);
        $this->assertContains('attendance.export_data', $adminPermissions);

        // Test teacher permissions
        $teacherPermissions = $this->teacherUser->getAttendancePermissions();
        $this->assertContains('attendance.view_assigned', $teacherPermissions);
        $this->assertContains('attendance.mark_assigned', $teacherPermissions);
        $this->assertContains('attendance.edit_assigned', $teacherPermissions);
        $this->assertContains('attendance.reports_assigned', $teacherPermissions);
        $this->assertNotContains('attendance.delete_all', $teacherPermissions);

        // Test student permissions
        $studentPermissions = $this->studentUser->getAttendancePermissions();
        $this->assertContains('attendance.view_own', $studentPermissions);
        $this->assertNotContains('attendance.mark_assigned', $studentPermissions);
        $this->assertNotContains('attendance.edit_assigned', $studentPermissions);
        $this->assertNotContains('attendance.delete_all', $studentPermissions);
        $this->assertNotContains('attendance.bulk_operations', $studentPermissions);
    }

    /** @test */
    public function test_api_authentication_and_authorization()
    {
        // Test unauthenticated API access
        $response = $this->getJson('/api/attendances');
        $response->assertStatus(401);

        // Test authenticated API access with proper role
        $this->actingAs($this->adminUser, 'sanctum');
        $response = $this->getJson('/api/attendances');
        $response->assertStatus(200);

        // Test authenticated API access with insufficient role
        $this->actingAs($this->studentUser, 'sanctum');
        $response = $this->postJson('/api/attendances', [
            'student_id' => 1,
            'status' => 'present'
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function test_security_logging_functionality()
    {
        // This would require setting up log testing
        // For now, we'll test that the security methods exist and work
        
        $this->actingAs($this->studentUser);
        
        // Attempt unauthorized access
        $response = $this->get('/admin/users');
        $response->assertStatus(403);
        
        // Check that the user has proper security methods
        $this->assertTrue(method_exists($this->studentUser, 'hasPermission'));
        $this->assertTrue(method_exists($this->studentUser, 'canAccessAttendance'));
        $this->assertTrue(method_exists($this->studentUser, 'getRoleLevel'));
    }

    /** @test */
    public function test_role_hierarchy_and_levels()
    {
        // Test role levels (based on actual Role model implementation)
        $this->assertEquals(5, $this->adminUser->getRoleLevel());
        $this->assertEquals(2, $this->teacherUser->getRoleLevel());
        $this->assertEquals(1, $this->studentUser->getRoleLevel());
        $this->assertEquals(5, $this->principalUser->getRoleLevel());

        // Test attendance access
        $this->assertTrue($this->adminUser->canAccessAttendance());
        $this->assertTrue($this->teacherUser->canAccessAttendance());
        $this->assertTrue($this->studentUser->canAccessAttendance());
        $this->assertTrue($this->principalUser->canAccessAttendance());
    }

    /** @test */
    public function test_multiple_role_validation()
    {
        // Test hasAnyRole method
        $this->assertTrue($this->adminUser->hasAnyRole(['admin', 'teacher']));
        $this->assertTrue($this->teacherUser->hasAnyRole(['teacher', 'student']));
        $this->assertFalse($this->studentUser->hasAnyRole(['admin', 'teacher']));

        // Test hasAnyPermission method
        $this->assertTrue($this->adminUser->hasAnyPermission(['view_attendance', 'mark_attendance']));
        $this->assertTrue($this->teacherUser->hasAnyPermission(['mark_attendance', 'delete_attendance']));
        $this->assertFalse($this->studentUser->hasAnyPermission(['mark_attendance', 'delete_attendance']));
    }
}
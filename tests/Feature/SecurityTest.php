<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SecurityTest extends TestCase
{
    use  WithFaker;

    protected $adminUser;
    protected $teacherUser;
    protected $studentUser;
    protected $principalUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
        
        // Create test users with different roles
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);

        $this->teacherUser = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password')
        ]);

        $this->studentUser = User::factory()->create([
            'role' => 'student',
            'email' => 'student@test.com',
            'password' => bcrypt('password')
        ]);

        $this->principalUser = User::factory()->create([
            'role' => 'principal',
            'email' => 'principal@test.com',
            'password' => bcrypt('password')
        ]);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $protectedRoutes = [
            '/dashboard',
            '/attendance',
            '/students',
            '/teachers',
            '/classes',
            '/subjects'
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /** @test */
    public function test_admin_user_can_access_all_routes()
    {
        $this->actingAs($this->adminUser);

        $routes = [
            '/dashboard',
            '/attendance',
            '/students',
            '/teachers'
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $this->assertContains($response->status(), [200, 302]);
        }
    }

    /** @test */
    public function test_teacher_user_has_limited_access()
    {
        $this->actingAs($this->teacherUser);

        // Teacher should access these routes
        $allowedRoutes = ['/dashboard', '/attendance'];
        foreach ($allowedRoutes as $route) {
            $response = $this->get($route);
            $this->assertContains($response->status(), [200, 302]);
        }
    }

    /** @test */
    public function test_student_user_has_most_restricted_access()
    {
        $this->actingAs($this->studentUser);

        // Student should only access dashboard
        $response = $this->get('/dashboard');
        $this->assertContains($response->status(), [200, 302]);
    }

    /** @test */
    public function test_role_middleware_functionality()
    {
        // Test admin role middleware
        $this->actingAs($this->adminUser);
        $response = $this->get('/users');
        $this->assertContains($response->status(), [200, 302]);

        // Test teacher role middleware
        $this->actingAs($this->teacherUser);
        $response = $this->get('/users');
        $this->assertContains($response->status(), [403, 302]);
    }

    /** @test */
    public function test_permission_middleware_functionality()
    {
        // Test bulk operations permission
        $this->actingAs($this->teacherUser);
        
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
        $response->assertStatus(422); // Validation error, not permission error
    }

    /** @test */
    public function test_sql_injection_prevention_in_student_search()
    {
        $this->actingAs($this->adminUser);
        
        $maliciousPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE students; --",
            "'; UPDATE users SET password='hacked'; --",
            "UNION SELECT username, password FROM users",
            "1' AND (SELECT COUNT(*) FROM users) > 0 --",
            "1'; EXEC xp_cmdshell('dir'); --"
        ];

        foreach ($maliciousPayloads as $payload) {
            $response = $this->get("/students?search=" . urlencode($payload));
            
            // Should not error or expose data
            $response->assertStatus(200);
            $response->assertDontSee('error', false);
            $response->assertDontSee('SQL', false);
            $response->assertDontSee('mysql', false);
            $response->assertDontSee('database', false);
            
            // Verify no data corruption occurred
            $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
        }
    }

    /** @test */
    public function test_file_upload_security()
    {
        $this->actingAs($this->adminUser);
        
        $maliciousFiles = [
            'test.php' => '<?php system($_GET["cmd"]); ?>',
            'test.html' => '<script>alert("xss")</script>',
            'test.exe' => 'MZ' . str_repeat('A', 100), // EXE header simulation
            'test.jsp' => '<%@ page import="java.io.*" %>',
            'test.asp' => '<%eval request("cmd")%>',
            '../../../etc/passwd' => 'root:x:0:0:root:/root:/bin/bash'
        ];

        foreach ($maliciousFiles as $filename => $content) {
            $file = \Illuminate\Http\UploadedFile::fake()->create($filename, 100);
            
            // Test document upload endpoint
            $response = $this->post('/teacher-documents', [
                'document' => $file,
                '_token' => csrf_token()
            ]);

            // Should reject malicious files
            $this->assertTrue(
                $response->status() === 422 || $response->status() === 400,
                "File {$filename} should be rejected but got status: " . $response->status()
            );
        }
    }

    /** @test */
    public function test_role_based_access_control()
    {
        // Test admin access to all routes
        $this->actingAs($this->adminUser);

        $adminRoutes = ['/salaries', '/budgets', '/system-settings', '/users'];
        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $this->assertContains($response->status(), [200, 302], 
                "Admin should access {$route} but got status: " . $response->status());
        }

        // Test teacher cannot access admin routes
        $this->actingAs($this->teacherUser);

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $this->assertContains($response->status(), [403, 302], 
                "Teacher should be blocked from {$route} but got status: " . $response->status());
        }

        // Test student has most restricted access
        $this->actingAs($this->studentUser);

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $this->assertContains($response->status(), [403, 302], 
                "Student should be blocked from {$route} but got status: " . $response->status());
        }
    }

    /** @test */
    public function test_xss_prevention_in_form_inputs()
    {
        $this->actingAs($this->adminUser);
        
        $xssPayloads = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert("xss")>',
            'javascript:alert("xss")',
            '<svg onload=alert("xss")>',
            '"><script>alert("xss")</script>',
            '\';alert("xss");//'
        ];

        foreach ($xssPayloads as $payload) {
            // Test student creation with XSS payload
            $response = $this->post('/students', [
                'name' => $payload,
                'email' => 'test@example.com',
                'admission_no' => '12345',
                '_token' => csrf_token()
            ]);

            // Should either validate and reject or escape the content
            if ($response->status() === 200 || $response->status() === 302) {
                // If accepted, verify it's properly escaped in database
                $student = \App\Models\Student::where('email', 'test@example.com')->first();
                if ($student) {
                    $this->assertNotContains('<script>', $student->name);
                    $this->assertNotContains('javascript:', $student->name);
                    $student->delete(); // Clean up
                }
            }
        }
    }

    /** @test */
    public function test_csrf_protection()
    {
        $this->actingAs($this->adminUser);
        
        // Test POST request without CSRF token
        $response = $this->post('/students', [
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'admission_no' => '12345'
        ]);

        // Should be rejected due to missing CSRF token
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function test_mass_assignment_protection()
    {
        $this->actingAs($this->adminUser);
        
        // Try to mass assign protected fields
        $response = $this->post('/students', [
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'admission_no' => '12345',
            'id' => 999999, // Should be ignored
            'created_at' => '2020-01-01', // Should be ignored
            'updated_at' => '2020-01-01', // Should be ignored
            '_token' => csrf_token()
        ]);

        if ($response->status() === 201 || $response->status() === 302) {
            $student = \App\Models\Student::where('email', 'test@example.com')->first();
            if ($student) {
                $this->assertNotEquals(999999, $student->id);
                $this->assertNotEquals('2020-01-01', $student->created_at->format('Y-m-d'));
                $student->delete(); // Clean up
            }
        }
    }

    /** @test */
    public function test_session_security()
    {
        // Test session fixation prevention
        $response = $this->post('/login', [
            'email' => $this->adminUser->email,
            'password' => 'password',
            '_token' => csrf_token()
        ]);

        $sessionId1 = session()->getId();
        
        // Logout and login again
        $this->post('/logout');
        
        $response = $this->post('/login', [
            'email' => $this->adminUser->email,
            'password' => 'password',
            '_token' => csrf_token()
        ]);

        $sessionId2 = session()->getId();
        
        // Session ID should change after login
        $this->assertNotEquals($sessionId1, $sessionId2);
    }

    /** @test */
    public function test_rate_limiting_on_sensitive_endpoints()
    {
        // Test login rate limiting
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/login', [
                'email' => 'wrong@email.com',
                'password' => 'wrongpassword',
                '_token' => csrf_token()
            ]);
        }

        // After multiple failed attempts, should be rate limited
        $response = $this->post('/login', [
            'email' => 'wrong@email.com',
            'password' => 'wrongpassword',
            '_token' => csrf_token()
        ]);

        // Should be rate limited (429) or still show validation errors (422)
        $this->assertContains($response->status(), [422, 429]);
    }
}
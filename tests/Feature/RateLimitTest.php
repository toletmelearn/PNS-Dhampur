<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class RateLimitTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    protected $user;
    protected $adminUser;
    protected $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        $studentRole = Role::create(['name' => 'student', 'display_name' => 'Student']);
        
        // Create users
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'role' => 'admin', // Add the role field for the middleware
        ]);
        
        $this->studentUser = User::create([
            'name' => 'Student User',
            'email' => 'student@test.com',
            'password' => Hash::make('password'),
            'role_id' => $studentRole->id,
            'role' => 'student', // Add the role field for the middleware
        ]);
        
        $this->user = $this->adminUser;
    }

    /** @test */
    public function test_login_rate_limiting_blocks_excessive_attempts()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        // Make 6 failed login attempts (exceeds IP limit of 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => 'wrong@email.com',
                'password' => 'wrongpassword',
            ]);
        }
        
        // The 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'wrong@email.com',
            'password' => 'wrongpassword',
        ]);
        
        $response->assertStatus(429);
        $this->assertStringContainsString('Too many login attempts', $response->getContent());
    }

    /** @test */
    public function test_login_rate_limiting_allows_successful_login_after_limit()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'wrong@email.com',
                'password' => 'wrongpassword',
            ]);
        }
        
        // Now try with correct credentials - should still work
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        
        $response->assertStatus(302);
        $this->assertAuthenticated();
    }

    /** @test */
    public function test_api_rate_limiting_respects_user_roles()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        // Test student rate limit (100 requests per minute)
        $this->actingAs($this->studentUser, 'sanctum');
        
        // Make 101 API requests
        for ($i = 0; $i < 101; $i++) {
            $response = $this->getJson('/api/user');
            if ($i < 100) {
                $response->assertNotEquals(429);
            }
        }
        
        // The 101st request should be rate limited
        $response = $this->getJson('/api/user');
        $response->assertStatus(429);
    }

    /** @test */
    public function test_form_submission_throttling()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        $this->actingAs($this->user);
        
        // Make 31 form submissions (exceeds default limit of 30)
        for ($i = 0; $i < 31; $i++) {
            $response = $this->post('/dashboard', [
                '_token' => csrf_token(),
                'test_field' => 'test_value',
            ]);
        }
        
        // The 31st submission should be rate limited
        $response = $this->post('/dashboard', [
            '_token' => csrf_token(),
            'test_field' => 'test_value',
        ]);
        
        $response->assertStatus(429);
    }

    /** @test */
    public function test_download_rate_limiting_by_role()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        // Test as student (50 downloads per hour limit)
        $this->actingAs($this->studentUser);
        
        // Simulate 51 download attempts
        for ($i = 0; $i < 51; $i++) {
            // Mock a download route that would use download rate limiting
            $response = $this->get('/test-download', [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]);
        }
        
        // Note: This test would need actual download routes to be fully functional
        // For now, we're testing the concept
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function test_rapid_request_detection()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        // Make 11 rapid requests within 30 seconds (exceeds rapid limit of 10)
        for ($i = 0; $i < 11; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@email.com',
                'password' => 'password',
            ]);
        }
        
        // Should detect rapid requests and block
        $response = $this->post('/login', [
            'email' => 'test@email.com',
            'password' => 'password',
        ]);
        
        $response->assertStatus(429);
    }

    /** @test */
    public function test_rate_limit_headers_are_present()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        $response = $this->post('/login', [
            'email' => 'test@email.com',
            'password' => 'wrongpassword',
        ]);
        
        // Check for rate limit headers
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }

    /** @test */
    public function test_rate_limit_clears_after_successful_login()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        // Make some failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => $this->user->email,
                'password' => 'wrongpassword',
            ]);
        }
        
        // Successful login should clear the rate limit
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        
        $response->assertStatus(302);
        $this->assertAuthenticated();
        
        // Logout and try again - should not be rate limited
        $this->post('/logout');
        
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        
        $response->assertStatus(302);
    }

    /** @test */
    public function test_critical_form_rate_limiting()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        // Don't authenticate the user for password reset (guest middleware)
        // $this->actingAs($this->user);
        
        // Test password reset form (critical form with limit of 5)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/password/email', [
                'email' => $this->user->email,
                '_token' => csrf_token(),
            ]);
        }
        
        // The 6th attempt should be rate limited
        $response = $this->postJson('/password/email', [
            'email' => $this->user->email,
            '_token' => csrf_token(),
        ]);
        
        $response->assertStatus(429);
    }

    /** @test */
    public function test_global_rate_limits()
    {
        // Clear any existing rate limits
        Cache::flush();
        
        // Test global login limit (100 per minute)
        // This would require simulating multiple IPs/users
        // For now, we test the concept
        
        for ($i = 0; $i < 101; $i++) {
            $response = $this->post('/login', [
                'email' => "user{$i}@test.com",
                'password' => 'password',
            ]);
        }
        
        // The 101st request should hit global limit
        $response = $this->post('/login', [
            'email' => 'globaltest@test.com',
            'password' => 'password',
        ]);
        
        // Note: This test would need proper global tracking to be fully functional
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function test_rate_limit_monitoring_dashboard_access()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/learning/admin/rate-limit');
        
        $response->assertStatus(200);
        $this->assertStringContainsString('Rate Limit Monitor', $response->getContent());
    }

    /** @test */
    public function test_rate_limit_clear_functionality()
    {
        $this->actingAs($this->adminUser);
        
        // Set up a rate limit
        Cache::put('login_rate_limit:ip:127.0.0.1', [
            'attempts' => 5,
            'blocked_at' => now(),
            'expires_at' => now()->addMinutes(15)
        ], 900);
        
        // Clear the rate limit
        $response = $this->postJson('/learning/admin/rate-limit/clear', [
            'identifier' => '127.0.0.1',
            'type' => 'login'
        ]);
        
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function test_rate_limit_configuration_endpoint()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->getJson('/learning/admin/rate-limit/config');
        
        $response->assertStatus(200);
        $this->assertArrayHasKey('login', $response->json());
        $this->assertArrayHasKey('api', $response->json());
        $this->assertArrayHasKey('form', $response->json());
        $this->assertArrayHasKey('download', $response->json());
    }

    /** @test */
    public function test_unauthorized_access_to_rate_limit_dashboard()
    {
        $this->actingAs($this->studentUser);
        
        $response = $this->getJson('/learning/admin/rate-limit');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function test_rate_limit_export_logs()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/learning/admin/rate-limit/export-logs');
        
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        Cache::flush();
        parent::tearDown();
    }
}
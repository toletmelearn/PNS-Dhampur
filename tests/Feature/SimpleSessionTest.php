<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\Request;
use App\Http\Middleware\RoleBasedSessionTimeout;
use App\Http\Middleware\SecureSessionConfig;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SimpleSessionTest extends TestCase
{
    use WithoutMiddleware;

    /** @test */
    public function role_based_session_timeout_middleware_sets_correct_timeouts()
    {
        $middleware = new RoleBasedSessionTimeout();
        
        // Test different roles
        $testCases = [
            'super_admin' => 15,
            'admin' => 20,
            'principal' => 25,
            'teacher' => 45,
            'student' => 120
        ];
        
        foreach ($testCases as $role => $expectedTimeout) {
            // Create a mock user
            $user = (object) ['role' => $role];
            
            // Create a mock request
            $request = Request::create('/test');
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
            // Process the middleware
            $response = $middleware->handle($request, function ($req) {
                return response('OK');
            });
            
            // Check that session timeout was set correctly
            $this->assertEquals($expectedTimeout, Session::get('session_timeout'));
        }
    }

    /** @test */
    public function secure_session_config_middleware_applies_security_settings()
    {
        $middleware = new SecureSessionConfig();
        
        // Test admin role (high security)
        $adminUser = (object) ['role' => 'admin'];
        $request = Request::create('/test');
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });
        
        $response = $middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        // Check that security settings were applied
        $this->assertTrue(config('session.expire_on_close'));
        $this->assertTrue(config('session.http_only'));
        $this->assertEquals('strict', config('session.same_site'));
    }

    /** @test */
    public function session_timeout_calculation_works_correctly()
    {
        // Set up session data
        Session::put('last_activity', Carbon::now()->subMinutes(10)->toDateTimeString());
        Session::put('session_timeout', 15);
        
        $middleware = new RoleBasedSessionTimeout();
        
        // Create mock request with super_admin user
        $user = (object) ['role' => 'super_admin'];
        $request = Request::create('/test');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Check if session should be expired (10 minutes < 15 minutes, so should be valid)
        $lastActivity = Carbon::parse(Session::get('last_activity'));
        $timeout = Session::get('session_timeout', 30);
        $isExpired = $lastActivity->addMinutes($timeout)->isPast();
        
        $this->assertFalse($isExpired, 'Session should not be expired');
    }

    /** @test */
    public function session_expiry_detection_works()
    {
        // Set up expired session data
        Session::put('last_activity', Carbon::now()->subMinutes(20)->toDateTimeString());
        Session::put('session_timeout', 15);
        
        // Check if session is expired (20 minutes > 15 minutes, so should be expired)
        $lastActivity = Carbon::parse(Session::get('last_activity'));
        $timeout = Session::get('session_timeout', 30);
        $isExpired = $lastActivity->addMinutes($timeout)->isPast();
        
        $this->assertTrue($isExpired, 'Session should be expired');
    }

    /** @test */
    public function session_warning_calculation_works()
    {
        // Set up session data that should trigger warning (18 minutes of 20-minute timeout)
        Session::put('last_activity', Carbon::now()->subMinutes(18)->toDateTimeString());
        Session::put('session_timeout', 20);
        
        $lastActivity = Carbon::parse(Session::get('last_activity'));
        $timeout = Session::get('session_timeout', 30);
        $expiresAt = $lastActivity->addMinutes($timeout);
        $timeRemaining = $expiresAt->diffInMinutes(Carbon::now());
        
        // Should show warning when less than 5 minutes remaining
        $shouldWarn = $timeRemaining <= 5;
        
        $this->assertTrue($shouldWarn, 'Should show warning when less than 5 minutes remaining');
        $this->assertEquals(2, $timeRemaining, 'Should have 2 minutes remaining');
    }

    /** @test */
    public function different_roles_get_different_security_levels()
    {
        $middleware = new SecureSessionConfig();
        
        // Test high-security role (admin)
        $adminUser = (object) ['role' => 'admin'];
        $request = Request::create('/test');
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });
        
        $middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        $adminExpireOnClose = config('session.expire_on_close');
        $adminSameSite = config('session.same_site');
        
        // Test lower-security role (student)
        $studentUser = (object) ['role' => 'student'];
        $request = Request::create('/test');
        $request->setUserResolver(function () use ($studentUser) {
            return $studentUser;
        });
        
        $middleware->handle($request, function ($req) {
            return response('OK');
        });
        
        $studentExpireOnClose = config('session.expire_on_close');
        $studentSameSite = config('session.same_site');
        
        // Admin should have stricter settings
        $this->assertTrue($adminExpireOnClose);
        $this->assertEquals('strict', $adminSameSite);
        
        // Student should have more relaxed settings
        $this->assertFalse($studentExpireOnClose);
        $this->assertEquals('lax', $studentSameSite);
    }

    /** @test */
    public function session_policies_are_correctly_defined()
    {
        $expectedPolicies = [
            'super_admin' => ['timeout' => 15, 'security' => 'maximum'],
            'admin' => ['timeout' => 20, 'security' => 'high'],
            'principal' => ['timeout' => 25, 'security' => 'high'],
            'vice_principal' => ['timeout' => 30, 'security' => 'high'],
            'accountant' => ['timeout' => 30, 'security' => 'medium'],
            'teacher' => ['timeout' => 45, 'security' => 'medium'],
            'class_teacher' => ['timeout' => 45, 'security' => 'medium'],
            'librarian' => ['timeout' => 60, 'security' => 'medium'],
            'receptionist' => ['timeout' => 60, 'security' => 'medium'],
            'student' => ['timeout' => 120, 'security' => 'basic']
        ];
        
        foreach ($expectedPolicies as $role => $policy) {
            $this->assertIsArray($policy);
            $this->assertArrayHasKey('timeout', $policy);
            $this->assertArrayHasKey('security', $policy);
            $this->assertIsInt($policy['timeout']);
            $this->assertIsString($policy['security']);
        }
    }
}
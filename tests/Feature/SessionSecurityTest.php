<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
// RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SessionSecurityTest extends TestCase
{
    // RefreshDatabase removed - using DatabaseTransactions in base TestCase

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with different roles
        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    /** @test */
    public function super_admin_has_shortest_session_timeout()
    {
        $this->actingAs($this->superAdmin);
        
        $response = $this->get('/api/session/info');
        
        $response->assertStatus(200);
        $response->assertJson([
            'role' => 'super_admin',
            'session_timeout_minutes' => 15
        ]);
    }

    /** @test */
    public function admin_has_appropriate_session_timeout()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/api/session/info');
        
        $response->assertStatus(200);
        $response->assertJson([
            'role' => 'admin',
            'session_timeout_minutes' => 20
        ]);
    }

    /** @test */
    public function teacher_has_medium_session_timeout()
    {
        $this->actingAs($this->teacher);
        
        $response = $this->get('/api/session/info');
        
        $response->assertStatus(200);
        $response->assertJson([
            'role' => 'teacher',
            'session_timeout_minutes' => 45
        ]);
    }

    /** @test */
    public function student_has_longest_session_timeout()
    {
        $this->actingAs($this->student);
        
        $response = $this->get('/api/session/info');
        
        $response->assertStatus(200);
        $response->assertJson([
            'role' => 'student',
            'session_timeout_minutes' => 120
        ]);
    }

    /** @test */
    public function session_timeout_middleware_logs_out_expired_sessions()
    {
        $this->actingAs($this->superAdmin);
        
        // Set last activity to 20 minutes ago (beyond 15-minute timeout for super_admin)
        Session::put('last_activity', Carbon::now()->subMinutes(20)->toDateTimeString());
        
        $response = $this->get('/dashboard');
        
        // Should be redirected to login due to session timeout
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function session_extends_when_user_is_active()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post('/api/session/extend');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'last_activity',
            'timeout_minutes',
            'user_role'
        ]);
    }

    /** @test */
    public function timeout_warning_appears_when_session_near_expiry()
    {
        $this->actingAs($this->admin);
        
        // Set last activity to 18 minutes ago (2 minutes before 20-minute timeout)
        Session::put('last_activity', Carbon::now()->subMinutes(18)->toDateTimeString());
        Session::put('session_timeout', 20);
        
        $response = $this->get('/api/session/timeout-warning');
        
        $response->assertStatus(200);
        $response->assertJson([
            'warning' => true,
            'time_remaining' => 2
        ]);
    }

    /** @test */
    public function no_timeout_warning_when_session_has_time_remaining()
    {
        $this->actingAs($this->admin);
        
        // Set last activity to 5 minutes ago (well within 20-minute timeout)
        Session::put('last_activity', Carbon::now()->subMinutes(5)->toDateTimeString());
        Session::put('session_timeout', 20);
        
        $response = $this->get('/api/session/timeout-warning');
        
        $response->assertStatus(200);
        $response->assertJson([
            'warning' => false
        ]);
    }

    /** @test */
    public function session_policies_endpoint_returns_correct_data()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/api/session/policies');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'policies' => [
                'super_admin' => [
                    'timeout_minutes',
                    'expire_on_close',
                    'security_level'
                ],
                'admin' => [
                    'timeout_minutes',
                    'expire_on_close',
                    'security_level'
                ]
            ]
        ]);
        
        $data = $response->json();
        $this->assertEquals(15, $data['policies']['super_admin']['timeout_minutes']);
        $this->assertEquals(20, $data['policies']['admin']['timeout_minutes']);
        $this->assertTrue($data['policies']['super_admin']['expire_on_close']);
    }

    /** @test */
    public function force_logout_works_correctly()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post('/api/session/logout');
        
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Logged out successfully'
        ]);
        
        $this->assertGuest();
    }

    /** @test */
    public function unauthenticated_users_cannot_access_session_endpoints()
    {
        $response = $this->get('/api/session/info');
        $response->assertStatus(401);
        
        $response = $this->post('/api/session/extend');
        $response->assertStatus(401);
        
        $response = $this->get('/api/session/timeout-warning');
        $response->assertStatus(401);
    }

    /** @test */
    public function session_security_middleware_applies_correct_settings_for_admin()
    {
        $this->actingAs($this->admin);
        
        // Make a request to trigger middleware
        $this->get('/dashboard');
        
        // Check that security settings are applied
        $this->assertTrue(config('session.expire_on_close'));
        $this->assertTrue(config('session.http_only'));
        $this->assertEquals('strict', config('session.same_site'));
    }

    /** @test */
    public function session_security_middleware_applies_different_settings_for_student()
    {
        $this->actingAs($this->student);
        
        // Make a request to trigger middleware
        $this->get('/dashboard');
        
        // Students should have less restrictive settings
        $this->assertFalse(config('session.expire_on_close'));
        $this->assertTrue(config('session.http_only'));
        $this->assertEquals('lax', config('session.same_site'));
    }

    /** @test */
    public function session_timeout_respects_role_hierarchy()
    {
        $roles = [
            'super_admin' => 15,
            'admin' => 20,
            'principal' => 25,
            'vice_principal' => 30,
            'accountant' => 30,
            'teacher' => 45,
            'class_teacher' => 45,
            'librarian' => 60,
            'receptionist' => 60,
            'student' => 120
        ];
        
        foreach ($roles as $role => $expectedTimeout) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user);
            
            $response = $this->get('/api/session/info');
            
            $response->assertStatus(200);
            $response->assertJson([
                'role' => $role,
                'session_timeout_minutes' => $expectedTimeout
            ]);
        }
    }

    /** @test */
    public function session_activity_updates_correctly()
    {
        $this->actingAs($this->admin);
        
        // Make initial request
        $this->get('/dashboard');
        $initialActivity = Session::get('last_activity');
        
        // Wait a moment and make another request
        sleep(1);
        $this->get('/dashboard');
        $updatedActivity = Session::get('last_activity');
        
        $this->assertNotEquals($initialActivity, $updatedActivity);
        $this->assertTrue(Carbon::parse($updatedActivity)->gt(Carbon::parse($initialActivity)));
    }
}
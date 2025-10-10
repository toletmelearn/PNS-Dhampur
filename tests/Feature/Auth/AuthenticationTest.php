<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Laravel\Sanctum\Sanctum;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role'
                    ],
                    'token',
                    'expires_at'
                ]);

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Invalid credentials'
                ]);

        $this->assertGuest();
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Account is inactive'
                ]);

        $this->assertGuest();
    }

    /** @test */
    public function login_validates_required_fields()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function login_validates_email_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Logged out successfully'
                ]);

        // Token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class
        ]);
    }

    /** @test */
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher',
            'phone' => '9876543210'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role'
                    ],
                    'token'
                ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'teacher'
        ]);
    }

    /** @test */
    public function registration_validates_required_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'email',
                    'password',
                    'role'
                ]);
    }

    /** @test */
    public function registration_validates_unique_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function registration_validates_password_confirmation()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
            'role' => 'teacher'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function registration_validates_role_values()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'invalid_role'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['role']);
    }

    /** @test */
    public function user_can_request_password_reset()
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/password/email', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Password reset link sent to your email'
                ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /** @test */
    public function password_reset_validates_email_exists()
    {
        $response = $this->postJson('/api/auth/password/email', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_reset_password_with_valid_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Password reset successfully'
                ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function password_reset_validates_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => 'test@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function authenticated_user_can_get_profile()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin'
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'role' => 'admin'
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_update_profile()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'John Smith',
            'phone' => '9876543210'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Profile updated successfully',
                    'user' => [
                        'name' => 'John Smith',
                        'phone' => '9876543210'
                    ]
                ]);

        $user->refresh();
        $this->assertEquals('John Smith', $user->name);
        $this->assertEquals('9876543210', $user->phone);
    }

    /** @test */
    public function user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123')
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Password changed successfully'
                ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function password_change_validates_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123')
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['current_password']);
    }

    /** @test */
    public function user_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token');

        Sanctum::actingAs($user, ['*'], $token->plainTextToken);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token',
                    'expires_at'
                ]);

        // Old token should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id
        ]);
    }

    /** @test */
    public function login_tracks_last_login_time()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'last_login_at' => null
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    /** @test */
    public function login_rate_limiting_works()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function user_sessions_can_be_managed()
    {
        $user = User::factory()->create();
        
        // Create multiple tokens (sessions)
        $token1 = $user->createToken('session-1');
        $token2 = $user->createToken('session-2');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/sessions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'sessions' => [
                        '*' => [
                            'id',
                            'name',
                            'last_used_at',
                            'created_at'
                        ]
                    ]
                ]);

        $this->assertCount(2, $response->json('sessions'));
    }

    /** @test */
    public function user_can_revoke_specific_session()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-session');

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/auth/sessions/{$token->accessToken->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Session revoked successfully'
                ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id
        ]);
    }

    /** @test */
    public function user_can_revoke_all_other_sessions()
    {
        $user = User::factory()->create();
        
        $currentToken = $user->createToken('current-session');
        $otherToken = $user->createToken('other-session');

        Sanctum::actingAs($user, ['*'], $currentToken->plainTextToken);

        $response = $this->deleteJson('/api/auth/sessions/others');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'All other sessions revoked successfully'
                ]);

        // Current token should still exist
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $currentToken->accessToken->id
        ]);

        // Other token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $otherToken->accessToken->id
        ]);
    }

    /** @test */
    public function two_factor_authentication_can_be_enabled()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/2fa/enable');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'qr_code',
                    'secret_key',
                    'backup_codes'
                ]);

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
    }

    /** @test */
    public function two_factor_authentication_can_be_verified()
    {
        $user = User::factory()->create([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_confirmed_at' => null
        ]);

        Sanctum::actingAs($user);

        // Mock the 2FA verification
        $this->mock(\PragmaRX\Google2FA\Google2FA::class, function ($mock) {
            $mock->shouldReceive('verifyKey')->andReturn(true);
        });

        $response = $this->postJson('/api/auth/2fa/confirm', [
            'code' => '123456'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Two-factor authentication enabled successfully'
                ]);

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
    }
}
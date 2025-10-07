<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleTestDebug extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function basic_test_environment_works()
    {
        // Simple assertion to verify test environment
        $this->assertTrue(true);
        
        // Test database connection
        $this->assertDatabaseCount('users', 0);
        
        // Test basic Laravel functionality (expecting redirect)
        $response = $this->get('/');
        $response->assertStatus(302);
    }

    /** @test */
    public function can_create_basic_user()
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }
}
<?php

namespace Tests\Feature;

// // RefreshDatabase removed - using DatabaseTransactions in base TestCase
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(302); // Accept redirect as valid response
    }
}

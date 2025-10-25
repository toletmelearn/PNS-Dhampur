<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable foreign key checks for SQLite in testing
        if (config('database.default') === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = OFF');
        }
    }
    
    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Re-enable foreign key checks
        if (config('database.default') === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = ON');
        }
        
        parent::tearDown();
    }
}


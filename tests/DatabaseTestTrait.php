<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

trait DatabaseTestTrait
{
    /**
     * Set up the database for testing.
     *
     * @return void
     */
    protected function setUpDatabase()
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Run migrations with force flag
        Artisan::call('migrate:fresh', ['--force' => true]);
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Skip test if database operation fails.
     *
     * @param string $message
     * @return void
     */
    protected function skipIfDatabaseError($message = 'Skipping test due to database setup issues')
    {
        $this->markTestSkipped($message);
    }
}
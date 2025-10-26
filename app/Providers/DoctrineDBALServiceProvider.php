<?php

namespace App\Providers;

use App\Database\Types\EnumType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\ServiceProvider;

class DoctrineDBALServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register ENUM type with Doctrine DBAL
        if (!Type::hasType('enum')) {
            Type::addType('enum', EnumType::class);
        }
    }
}
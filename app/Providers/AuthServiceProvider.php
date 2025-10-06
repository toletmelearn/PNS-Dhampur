<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Define authorization gates
        Gate::define('view_audit_trails', function ($user) {
            return $user->hasPermission('view_audit_trails');
        });

        Gate::define('view_rate_limit_dashboard', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('principal');
        });

        Gate::define('manage_rate_limits', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('principal');
        });

        Gate::define('export_rate_limit_logs', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('principal');
        });
    }
}

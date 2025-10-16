<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Load additional authenticated web routes
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $key = $user?->id ?: $request->ip();

            // Detect Super Admin role or Super Admin API namespace
            $isSuperAdmin = $user && method_exists($user, 'hasRole') ? $user->hasRole('super_admin') : false;
            $path = ltrim($request->path(), '/'); // e.g., "api/super-admin/users"
            $isSuperAdminApi = str_starts_with($path, 'api/super-admin');

            // Provide significantly higher thresholds to Super Admins and their API namespace
            if ($isSuperAdmin || $isSuperAdminApi) {
                return Limit::perMinute(600)->by($key);
            }

            // Default API limit
            return Limit::perMinute(60)->by($key);
        });
    }
}

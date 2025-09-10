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
    public const HOME = '/home'; // Or whatever your main SPA starting point is

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Define rate limiters (usually for API)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Define your application's routes
        $this->routes(function () {
            // Web routes (no prefix, uses 'web' middleware group by default from Kernel)
            // This is where your login, logout, and main SPA catch-all routes will be loaded from
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // API routes (prefixed with 'api', uses 'api' middleware group by default from Kernel)
            // This is where your segregated API routes will be loaded from
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
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
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('meapi', function (Request $request) {
            $session = $request->session()->get('triethic', null);
            if ($session === null) return  Limit::perMinute(10)->by($request->ip());
            return Limit::perMinute(100)->by($session['user']['id']);
            //return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('meapicollecteur', function (Request $request) {
            $session = $request->session()->get('triethic', null);
            if ($session === null) return  Limit::perMinute(20)->by($request->ip());
            return Limit::perMinute(100)->by($session['user']['id']);
            //return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('meweb', function (Request $request) {
            $session = $request->session()->get('triethic', null);
            if ($session === null) return  Limit::perMinute(10)->by($request->ip());
            return Limit::perMinute(100)->by($session['user']['id']);
            //return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('mestrong', function (Request $request) {
            return Limit::perMinute(10);
        });

    }
}

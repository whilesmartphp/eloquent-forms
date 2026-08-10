<?php

namespace Whilesmart\Forms;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Whilesmart\Forms\Challenges\ChallengeManager;
use Whilesmart\Forms\Destinations\DestinationManager;
use Whilesmart\Forms\Interfaces\ResponseFormatterInterface;

class FormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/eloquent-forms.php',
            'eloquent-forms'
        );

        $this->app->bind(ResponseFormatterInterface::class, function () {
            $formatter = config('eloquent-forms.response_formatter');

            return new $formatter();
        });

        $this->app->singleton(
            DestinationManager::class,
            fn ($app) => new DestinationManager($app)
        );

        $this->app->singleton(
            ChallengeManager::class,
            fn ($app) => new ChallengeManager($app)
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'eloquent-forms');

        $this->registerRateLimiter();

        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], ['eloquent-forms', 'eloquent-forms-migrations']);

        $this->publishes([
            __DIR__ . '/../config/eloquent-forms.php' => config_path('eloquent-forms.php'),
        ], ['eloquent-forms', 'eloquent-forms-config']);

        if (config('eloquent-forms.register_routes', true)) {
            $prefix = config('eloquent-forms.route_prefix', 'api');
            $register = fn () => $this->loadRoutesFrom(__DIR__ . '/../routes/eloquent-forms.php');

            if ($prefix) {
                Route::prefix($prefix)->group($register);
            } else {
                $register();
            }
        }
    }

    private function registerRateLimiter(): void
    {
        $perMinute = (int) config('eloquent-forms.protection.rate_limit_per_minute', 10);

        RateLimiter::for('eloquent-forms', function (Request $request) use ($perMinute) {
            return Limit::perMinute($perMinute)->by($request->ip());
        });
    }
}

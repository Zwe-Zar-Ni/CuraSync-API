<?php

namespace App\Providers;

use App\Auth\JwtGuard;
use App\Services\JwtService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard(
                $app['auth']->createUserProvider($config['provider'] ?? null),
                $app->make(JwtService::class),
                $app->make('request'),
            );
        });

        DB::listen(function (QueryExecuted $query) {
            // ? Logs the SQL with its execution time and bindings
            // Log::info(
            //     $query->sql,
            //     ['bindings' => $query->bindings, 'time' => $query->time . 'ms']
            // );
        });
    }
}

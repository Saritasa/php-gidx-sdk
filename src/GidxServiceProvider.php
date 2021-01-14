<?php

namespace GidxSDK;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers GidxSDK migrations, publishes configs, etc.
 */
class GidxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/gidx.php' => config_path('gidx.php'),
        ], 'config');

        $this->registerMigrations();
        $this->registerRoutes();
    }

    /**
     * Register the package migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register route to handle webhook.
     */
    private function registerRoutes()
    {
        Route::group([
            'prefix' => 'gidx',
            'namespace' => 'GidxSDK\Http\Controllers',
            'as' => 'gidx.',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}

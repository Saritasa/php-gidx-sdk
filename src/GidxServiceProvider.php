<?php

namespace GidxSDK;

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
}

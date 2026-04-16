<?php

namespace Developernauts\NativephpMobileLocales;

use Developernauts\NativephpMobileLocales\Commands\SyncAndroidLocales;
use Developernauts\NativephpMobileLocales\Commands\SyncAndroidManifest;
use Developernauts\NativephpMobileLocales\Commands\SyncIosLocales;
use Developernauts\NativephpMobileLocales\Commands\SyncLocales;
use Illuminate\Support\ServiceProvider;

class NativephpMobileLocalesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'nativephp-mobile-locales');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'nativephp-mobile-locales');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('nativephp-mobile-locales.php'),
            ], 'config');

            $this->commands([
                SyncLocales::class,
                SyncIosLocales::class,
                SyncAndroidLocales::class,
                SyncAndroidManifest::class,
            ]);

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/nativephp-mobile-locales'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/nativephp-mobile-locales'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/nativephp-mobile-locales'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'nativephp-mobile-locales');

        // Register the main class to use with the facade
        $this->app->singleton('nativephp-mobile-locales', function ($app) {
            return new NativephpMobileLocales(
                (array) $app['config']->get('nativephp-mobile-locales.locales', [])
            );
        });

        $this->app->alias('nativephp-mobile-locales', NativephpMobileLocales::class);
    }
}

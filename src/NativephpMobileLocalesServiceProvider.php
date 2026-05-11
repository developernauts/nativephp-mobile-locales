<?php

namespace Developernauts\NativephpMobileLocales;

use Developernauts\NativephpMobileLocales\Commands\SyncAndroidLocales;
use Developernauts\NativephpMobileLocales\Commands\SyncAndroidManifest;
use Developernauts\NativephpMobileLocales\Commands\SyncIosLocales;
use Developernauts\NativephpMobileLocales\Commands\SyncLocales;
use Illuminate\Support\ServiceProvider;

class NativephpMobileLocalesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('mobile-locales.php'),
        ], 'nativephp-mobile-locales-config');

        $this->commands([
            SyncLocales::class,
            SyncIosLocales::class,
            SyncAndroidLocales::class,
            SyncAndroidManifest::class,
        ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'mobile-locales');

        $this->app->singleton('mobile-locales', function ($app) {
            return new NativephpMobileLocales(
                (array) $app['config']->get('mobile-locales.locales', [])
            );
        });

        $this->app->alias('mobile-locales', NativephpMobileLocales::class);
    }
}

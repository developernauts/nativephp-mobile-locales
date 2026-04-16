<?php

namespace Developernauts\NativephpMobileLocales\Commands;

use Illuminate\Console\Command;

class SyncLocales extends Command
{
    protected $signature = 'nativephp-mobile-locales:sync
                        {--platform= : Platform passed by NativePHP hooks}
                        {--build-path= : Build path passed by NativePHP hooks}
                        {--plugin-path= : Plugin path passed by NativePHP hooks}
                        {--app-id= : App id passed by NativePHP hooks}
                        {--config= : Config payload passed by NativePHP hooks}
                        {--plugins= : Plugins payload passed by NativePHP hooks}';

    protected $description = 'NativePHP pre_compile hook entry point — routes to the platform-specific sync command.';

    public function handle(): int
    {
        $forward = array_filter([
            '--platform' => $this->option('platform'),
            '--build-path' => $this->option('build-path'),
            '--plugin-path' => $this->option('plugin-path'),
            '--app-id' => $this->option('app-id'),
            '--config' => $this->option('config'),
            '--plugins' => $this->option('plugins'),
        ], static fn ($value) => $value !== null && $value !== '');

        return match ($this->option('platform')) {
            'ios' => $this->call('nativephp-mobile-locales:sync-ios', $forward),
            'android' => max(
                $this->call('nativephp-mobile-locales:sync-android', $forward),
                $this->call('nativephp-mobile-locales:sync-android-manifest', $forward),
            ),
            default => max(
                $this->call('nativephp-mobile-locales:sync-ios', $forward),
                $this->call('nativephp-mobile-locales:sync-android', $forward),
                $this->call('nativephp-mobile-locales:sync-android-manifest', $forward),
            ),
        };
    }
}

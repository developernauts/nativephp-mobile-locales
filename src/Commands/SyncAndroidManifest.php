<?php

namespace Developernauts\NativephpMobileLocales\Commands;

use Developernauts\NativephpMobileLocales\NativephpMobileLocales;
use DOMDocument;
use DOMElement;
use Illuminate\Console\Command;

class SyncAndroidManifest extends Command
{
    protected const ANDROID_NS = 'http://schemas.android.com/apk/res/android';

    protected const LOCALE_CONFIG_VALUE = '@xml/locales_config';

    protected $signature = 'nativephp-mobile-locales:sync-android-manifest
                        {--platform= : Platform passed by NativePHP hooks}
                        {--build-path= : Build path passed by NativePHP hooks}
                        {--plugin-path= : Plugin path passed by NativePHP hooks}
                        {--app-id= : App id passed by NativePHP hooks}
                        {--config= : Config payload passed by NativePHP hooks}
                        {--plugins= : Plugins payload passed by NativePHP hooks}
                        {--path= : Override the AndroidManifest.xml path}';

    protected $description = 'Add android:localeConfig="@xml/locales_config" to the Android <application> element.';

    public function handle(NativephpMobileLocales $locales): int
    {
        $platform = $this->option('platform');

        if ($platform !== null && $platform !== '' && $platform !== 'android') {
            return self::SUCCESS;
        }

        if ($locales->all() === []) {
            $this->components->warn('No locales configured — skipping AndroidManifest.xml update.');

            return self::SUCCESS;
        }

        $path = $this->resolveManifestPath();

        if (! is_file($path)) {
            $this->components->error("AndroidManifest.xml not found at: {$path}");

            return self::FAILURE;
        }

        $manifest = file_get_contents($path);

        if (str_contains($manifest, 'android:localeConfig')) {
            $this->components->info('AndroidManifest.xml: <application android:localeConfig="@xml/locales_config"> already set.');

            return self::SUCCESS;
        }

        $manifest = preg_replace(
            '/(<application\b[^>]*?)(>)/s',
            '$1 android:localeConfig="'.self::LOCALE_CONFIG_VALUE.'"$2',
            $manifest,
            1
        );

        if ($manifest === null) {
            $this->components->error('Failed to parse AndroidManifest.xml via Regex.');

            return self::FAILURE;
        }

        file_put_contents($path, $manifest);

        $this->components->info('AndroidManifest.xml: <application android:localeConfig="@xml/locales_config"> set.');

        return self::SUCCESS;
    }

    protected function resolveManifestPath(): string
    {
        if ($override = $this->option('path')) {
            return $override;
        }

        if ($buildPath = $this->option('build-path')) {
            return rtrim($buildPath, DIRECTORY_SEPARATOR).'/app/src/main/AndroidManifest.xml';
        }

        return base_path('nativephp/android/app/src/main/AndroidManifest.xml');
    }
}

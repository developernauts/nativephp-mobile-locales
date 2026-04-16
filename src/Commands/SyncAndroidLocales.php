<?php

namespace Developernauts\NativephpMobileLocales\Commands;

use Developernauts\NativephpMobileLocales\NativephpMobileLocales;
use DOMDocument;
use Illuminate\Console\Command;

class SyncAndroidLocales extends Command
{
    protected $signature = 'nativephp-mobile-locales:sync-android
                        {--platform= : Platform passed by NativePHP hooks}
                        {--build-path= : Build path passed by NativePHP hooks}
                        {--plugin-path= : Plugin path passed by NativePHP hooks}
                        {--app-id= : App id passed by NativePHP hooks}
                        {--config= : Config payload passed by NativePHP hooks}
                        {--plugins= : Plugins payload passed by NativePHP hooks}
                        {--path= : Override the locales_config.xml path}';

    protected $description = 'Write the configured locales into Android res/xml/locales_config.xml.';

    public function handle(NativephpMobileLocales $locales): int
    {
        $platform = $this->option('platform');

        if ($platform !== null && $platform !== '' && $platform !== 'android') {
            return self::SUCCESS;
        }

        $values = $locales->toAndroid();

        if ($values === []) {
            $this->components->warn('No locales configured — skipping locales_config.xml update.');

            return self::SUCCESS;
        }

        $path = $this->resolveLocalesConfigPath();
        $directory = dirname($path);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->components->error("Unable to create directory: {$directory}");

            return self::FAILURE;
        }

        if (file_put_contents($path, $this->render($values)) === false) {
            $this->components->error("Failed to write locales_config.xml at: {$path}");

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'locales_config.xml written with: %s',
            implode(', ', $values)
        ));

        return self::SUCCESS;
    }

    protected function resolveLocalesConfigPath(): string
    {
        if ($override = $this->option('path')) {
            return $override;
        }

        if ($buildPath = $this->option('build-path')) {
            return rtrim($buildPath, DIRECTORY_SEPARATOR).'/app/src/main/res/xml/locales_config.xml';
        }

        return base_path('nativephp/android/app/src/main/res/xml/locales_config.xml');
    }

    /**
     * @param  array<int, string>  $locales
     */
    protected function render(array $locales): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $root = $dom->createElement('locale-config');
        $root->setAttribute('xmlns:android', 'http://schemas.android.com/apk/res/android');

        foreach ($locales as $locale) {
            $node = $dom->createElement('locale');
            $node->setAttribute('android:name', $locale);
            $root->appendChild($node);
        }

        $dom->appendChild($root);

        return $dom->saveXML();
    }
}

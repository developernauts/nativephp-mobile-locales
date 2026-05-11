<?php

namespace Developernauts\NativephpMobileLocales\Tests\Feature;

use Developernauts\NativephpMobileLocales\Tests\TestCase;
use DOMDocument;
use DOMXPath;

class SyncLocalesTest extends TestCase
{
    private string $buildDir;
    private string $plistPath;
    private string $xmlPath;
    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildDir = sys_get_temp_dir().'/nativephp_sync_'.uniqid();
        mkdir($this->buildDir.'/NativePHP', 0755, true);
        mkdir($this->buildDir.'/app/src/main/res/xml', 0755, true);

        $this->plistPath = $this->buildDir.'/NativePHP/Info.plist';
        $this->xmlPath = $this->buildDir.'/app/src/main/res/xml/locales_config.xml';
        $this->manifestPath = $this->buildDir.'/app/src/main/AndroidManifest.xml';

        file_put_contents($this->plistPath, $this->basePlist());
        file_put_contents($this->manifestPath, $this->baseManifest());
    }

    protected function tearDown(): void
    {
        $this->deleteRecursive($this->buildDir);
        parent::tearDown();
    }

    private function basePlist(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<plist version="1.0">
<dict>
    <key>CFBundleIdentifier</key>
    <string>com.example.app</string>
</dict>
</plist>
XML;
    }

    private function baseManifest(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <application android:label="@string/app_name">
    </application>
</manifest>
XML;
    }

    private function deleteRecursive(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path.'/'.$entry;
            is_dir($full) ? $this->deleteRecursive($full) : unlink($full);
        }
        rmdir($path);
    }

    public function test_platform_ios_only_updates_plist(): void
    {
        $this->artisan('nativephp-mobile-locales:sync', [
            '--platform' => 'ios',
            '--build-path' => $this->buildDir,
        ])->assertSuccessful();

        $this->assertStringContainsString('CFBundleLocalizations', file_get_contents($this->plistPath));
        $this->assertFileDoesNotExist($this->xmlPath);
        $this->assertStringNotContainsString('localeConfig', file_get_contents($this->manifestPath));
    }

    public function test_platform_android_only_updates_android_files(): void
    {
        $this->artisan('nativephp-mobile-locales:sync', [
            '--platform' => 'android',
            '--build-path' => $this->buildDir,
        ])->assertSuccessful();

        $this->assertStringNotContainsString('CFBundleLocalizations', file_get_contents($this->plistPath));
        $this->assertFileExists($this->xmlPath);
        $this->assertStringContainsString('localeConfig', file_get_contents($this->manifestPath));
    }

    public function test_no_platform_updates_all_files(): void
    {
        $this->artisan('nativephp-mobile-locales:sync', [
            '--build-path' => $this->buildDir,
        ])->assertSuccessful();

        $this->assertStringContainsString('CFBundleLocalizations', file_get_contents($this->plistPath));
        $this->assertFileExists($this->xmlPath);
        $this->assertStringContainsString('localeConfig', file_get_contents($this->manifestPath));
    }

    public function test_returns_success_when_no_locales_configured(): void
    {
        $this->app['config']->set('mobile-locales.locales', []);

        $this->artisan('nativephp-mobile-locales:sync', [
            '--build-path' => $this->buildDir,
        ])->assertSuccessful();
    }

    public function test_propagates_failure_when_ios_plist_is_missing(): void
    {
        unlink($this->plistPath);

        $this->artisan('nativephp-mobile-locales:sync', [
            '--platform' => 'ios',
            '--build-path' => $this->buildDir,
        ])->assertFailed();
    }

    public function test_propagates_failure_when_android_manifest_is_missing(): void
    {
        unlink($this->manifestPath);

        $this->artisan('nativephp-mobile-locales:sync', [
            '--platform' => 'android',
            '--build-path' => $this->buildDir,
        ])->assertFailed();
    }
}

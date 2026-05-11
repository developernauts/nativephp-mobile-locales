<?php

namespace Developernauts\NativephpMobileLocales\Tests\Feature;

use Developernauts\NativephpMobileLocales\Tests\TestCase;
use DOMDocument;
use DOMXPath;

class SyncAndroidManifestTest extends TestCase
{
    private string $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manifest = tempnam(sys_get_temp_dir(), 'manifest_').'.xml';
    }

    protected function tearDown(): void
    {
        if (is_file($this->manifest)) {
            unlink($this->manifest);
        }
        parent::tearDown();
    }

    private function writeManifest(string $xml): void
    {
        file_put_contents($this->manifest, $xml);
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

    private function readLocaleConfig(): ?string
    {
        $dom = new DOMDocument();
        $dom->load($this->manifest);
        $app = $dom->getElementsByTagName('application')->item(0);

        return $app?->getAttributeNS('http://schemas.android.com/apk/res/android', 'localeConfig') ?: null;
    }

    public function test_adds_locale_config_attribute_to_application_element(): void
    {
        $this->writeManifest($this->baseManifest());

        $this->artisan('nativephp-mobile-locales:sync-android-manifest', ['--path' => $this->manifest])
            ->assertSuccessful();

        $this->assertSame('@xml/locales_config', $this->readLocaleConfig());
    }

    public function test_is_idempotent(): void
    {
        $this->writeManifest($this->baseManifest());

        $this->artisan('nativephp-mobile-locales:sync-android-manifest', ['--path' => $this->manifest])->assertSuccessful();
        $first = file_get_contents($this->manifest);

        $this->artisan('nativephp-mobile-locales:sync-android-manifest', ['--path' => $this->manifest])->assertSuccessful();
        $second = file_get_contents($this->manifest);

        $this->assertSame($first, $second);
        $this->assertSame(1, substr_count($second, 'localeConfig'));
    }

    public function test_skips_and_succeeds_when_no_locales_configured(): void
    {
        $this->app['config']->set('mobile-locales.locales', []);
        $this->writeManifest($this->baseManifest());

        $this->artisan('nativephp-mobile-locales:sync-android-manifest', ['--path' => $this->manifest])
            ->assertSuccessful();

        $this->assertNull($this->readLocaleConfig());
    }

    public function test_skips_when_platform_option_is_ios(): void
    {
        $this->writeManifest($this->baseManifest());

        $this->artisan('nativephp-mobile-locales:sync-android-manifest', [
            '--path' => $this->manifest,
            '--platform' => 'ios',
        ])->assertSuccessful();

        $this->assertNull($this->readLocaleConfig());
    }

    public function test_fails_when_manifest_does_not_exist(): void
    {
        $this->artisan('nativephp-mobile-locales:sync-android-manifest', ['--path' => '/tmp/nonexistent_'.uniqid().'.xml'])
            ->assertFailed();
    }

    public function test_build_path_option_resolves_manifest_location(): void
    {
        $buildDir = sys_get_temp_dir().'/nativephp_manifest_'.uniqid();
        mkdir($buildDir.'/app/src/main', 0755, true);
        $manifestPath = $buildDir.'/app/src/main/AndroidManifest.xml';
        file_put_contents($manifestPath, $this->baseManifest());

        $this->artisan('nativephp-mobile-locales:sync-android-manifest', ['--build-path' => $buildDir])
            ->assertSuccessful();

        $dom = new DOMDocument();
        $dom->load($manifestPath);
        $attr = $dom->getElementsByTagName('application')->item(0)
            ->getAttributeNS('http://schemas.android.com/apk/res/android', 'localeConfig');

        $this->assertSame('@xml/locales_config', $attr);

        unlink($manifestPath);
        rmdir($buildDir.'/app/src/main');
        rmdir($buildDir.'/app/src');
        rmdir($buildDir.'/app');
        rmdir($buildDir);
    }
}

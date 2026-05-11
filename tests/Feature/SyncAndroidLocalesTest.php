<?php

namespace Developernauts\NativephpMobileLocales\Tests\Feature;

use Developernauts\NativephpMobileLocales\Tests\TestCase;

class SyncAndroidLocalesTest extends TestCase
{
    private string $xmlPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xmlPath = sys_get_temp_dir().'/locales_config_'.uniqid().'.xml';
    }

    protected function tearDown(): void
    {
        if (is_file($this->xmlPath)) {
            unlink($this->xmlPath);
        }
        parent::tearDown();
    }

    public function test_writes_locales_config_xml_with_correct_locale_names(): void
    {
        $this->artisan('nativephp-mobile-locales:sync-android', ['--path' => $this->xmlPath])
            ->assertSuccessful();

        $this->assertFileExists($this->xmlPath);
        $content = file_get_contents($this->xmlPath);
        $this->assertStringContainsString('android:name="en"', $content);
        $this->assertStringContainsString('android:name="pt-rBR"', $content);
    }

    public function test_output_is_valid_xml_with_locale_config_root(): void
    {
        $this->artisan('nativephp-mobile-locales:sync-android', ['--path' => $this->xmlPath])
            ->assertSuccessful();

        $dom = new \DOMDocument();
        $this->assertTrue($dom->load($this->xmlPath));
        $this->assertSame('locale-config', $dom->documentElement->nodeName);
    }

    public function test_overwrites_an_existing_file(): void
    {
        file_put_contents($this->xmlPath, '<old-content/>');

        $this->artisan('nativephp-mobile-locales:sync-android', ['--path' => $this->xmlPath])
            ->assertSuccessful();

        $this->assertStringNotContainsString('old-content', file_get_contents($this->xmlPath));
        $this->assertStringContainsString('locale-config', file_get_contents($this->xmlPath));
    }

    public function test_skips_and_succeeds_when_no_locales_configured(): void
    {
        $this->app['config']->set('mobile-locales.locales', []);

        $this->artisan('nativephp-mobile-locales:sync-android', ['--path' => $this->xmlPath])
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->xmlPath);
    }

    public function test_skips_when_platform_option_is_ios(): void
    {
        $this->artisan('nativephp-mobile-locales:sync-android', [
            '--path' => $this->xmlPath,
            '--platform' => 'ios',
        ])->assertSuccessful();

        $this->assertFileDoesNotExist($this->xmlPath);
    }

    public function test_creates_parent_directory_if_missing(): void
    {
        $deep = sys_get_temp_dir().'/nativephp_'.uniqid().'/res/xml/locales_config.xml';

        $this->artisan('nativephp-mobile-locales:sync-android', ['--path' => $deep])
            ->assertSuccessful();

        $this->assertFileExists($deep);

        unlink($deep);
        rmdir(dirname($deep));
        rmdir(dirname(dirname($deep)));
        rmdir(dirname(dirname(dirname($deep))));
    }

    public function test_build_path_option_resolves_xml_location(): void
    {
        $buildDir = sys_get_temp_dir().'/nativephp_and_'.uniqid();
        $xmlDir = $buildDir.'/app/src/main/res/xml';
        mkdir($xmlDir, 0755, true);

        $this->artisan('nativephp-mobile-locales:sync-android', ['--build-path' => $buildDir])
            ->assertSuccessful();

        $this->assertFileExists($xmlDir.'/locales_config.xml');

        unlink($xmlDir.'/locales_config.xml');
        foreach (array_reverse(explode('/', str_replace(sys_get_temp_dir().'/', '', $xmlDir))) as $segment) {
            @rmdir(sys_get_temp_dir().'/'.$segment);
        }
        $this->deleteRecursive($buildDir);
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
}

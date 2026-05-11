<?php

namespace Developernauts\NativephpMobileLocales\Tests\Feature;

use Developernauts\NativephpMobileLocales\Tests\TestCase;
use DOMDocument;
use DOMXPath;

class SyncIosLocalesTest extends TestCase
{
    private string $plist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plist = tempnam(sys_get_temp_dir(), 'plist_').'.plist';
    }

    protected function tearDown(): void
    {
        if (is_file($this->plist)) {
            unlink($this->plist);
        }
        parent::tearDown();
    }

    private function writePlist(string $xml): void
    {
        file_put_contents($this->plist, $xml);
    }

    private function basePlist(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>CFBundleIdentifier</key>
    <string>com.example.app</string>
</dict>
</plist>
XML;
    }

    private function readLocalizations(): array
    {
        return $this->readLocalizationsFrom($this->plist);
    }

    private function readLocalizationsFrom(string $path): array
    {
        $dom = new DOMDocument();
        $dom->load($path);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//key[.="CFBundleLocalizations"]/following-sibling::array[1]/string');
        $values = [];
        foreach ($nodes as $node) {
            $values[] = $node->textContent;
        }

        return $values;
    }

    public function test_writes_cFBundleLocalizations_when_key_is_absent(): void
    {
        $this->writePlist($this->basePlist());

        $this->artisan('nativephp-mobile-locales:sync-ios', ['--path' => $this->plist])
            ->assertSuccessful();

        $this->assertSame(['en', 'pt-BR'], $this->readLocalizations());
    }

    public function test_replaces_existing_cFBundleLocalizations(): void
    {
        $this->writePlist(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<plist version="1.0">
<dict>
    <key>CFBundleLocalizations</key>
    <array>
        <string>de</string>
        <string>fr</string>
    </array>
</dict>
</plist>
XML);

        $this->artisan('nativephp-mobile-locales:sync-ios', ['--path' => $this->plist])
            ->assertSuccessful();

        $locales = $this->readLocalizations();
        $this->assertSame(['en', 'pt-BR'], $locales);
        $this->assertNotContains('de', $locales);
        $this->assertNotContains('fr', $locales);
    }

    public function test_skips_and_succeeds_when_no_locales_configured(): void
    {
        $this->app['config']->set('mobile-locales.locales', []);
        $this->writePlist($this->basePlist());

        $this->artisan('nativephp-mobile-locales:sync-ios', ['--path' => $this->plist])
            ->assertSuccessful();

        $this->assertStringNotContainsString('CFBundleLocalizations', file_get_contents($this->plist));
    }

    public function test_skips_when_platform_option_is_android(): void
    {
        $this->writePlist($this->basePlist());

        $this->artisan('nativephp-mobile-locales:sync-ios', [
            '--path' => $this->plist,
            '--platform' => 'android',
        ])->assertSuccessful();

        $this->assertStringNotContainsString('CFBundleLocalizations', file_get_contents($this->plist));
    }

    public function test_fails_when_plist_does_not_exist(): void
    {
        $this->artisan('nativephp-mobile-locales:sync-ios', ['--path' => '/tmp/nonexistent_'.uniqid().'.plist'])
            ->assertFailed();
    }

    public function test_build_path_option_resolves_plist_location(): void
    {
        $buildDir = sys_get_temp_dir().'/nativephp_ios_'.uniqid();
        $plistDir = $buildDir.'/NativePHP';
        mkdir($plistDir, 0755, true);
        $plistPath = $plistDir.'/Info.plist';
        file_put_contents($plistPath, $this->basePlist());

        $this->artisan('nativephp-mobile-locales:sync-ios', ['--build-path' => $buildDir])
            ->assertSuccessful();

        $this->assertStringContainsString('CFBundleLocalizations', file_get_contents($plistPath));

        unlink($plistPath);
        rmdir($plistDir);
        rmdir($buildDir);
    }

    public function test_updates_both_device_and_simulator_plists_when_both_exist(): void
    {
        $buildDir = sys_get_temp_dir().'/nativephp_ios_'.uniqid();
        $devicePlistDir = $buildDir.'/NativePHP';
        mkdir($devicePlistDir, 0755, true);
        $devicePlist = $devicePlistDir.'/Info.plist';
        $simulatorPlist = $buildDir.'/NativePHP-simulator-Info.plist';
        file_put_contents($devicePlist, $this->basePlist());
        file_put_contents($simulatorPlist, $this->basePlist());

        $this->artisan('nativephp-mobile-locales:sync-ios', ['--build-path' => $buildDir])
            ->assertSuccessful();

        $this->assertSame(['en', 'pt-BR'], $this->readLocalizationsFrom($devicePlist));
        $this->assertSame(['en', 'pt-BR'], $this->readLocalizationsFrom($simulatorPlist));

        unlink($devicePlist);
        unlink($simulatorPlist);
        rmdir($devicePlistDir);
        rmdir($buildDir);
    }

    public function test_updates_simulator_plist_and_succeeds_when_only_simulator_exists(): void
    {
        $buildDir = sys_get_temp_dir().'/nativephp_ios_'.uniqid();
        mkdir($buildDir, 0755, true);
        $simulatorPlist = $buildDir.'/NativePHP-simulator-Info.plist';
        file_put_contents($simulatorPlist, $this->basePlist());

        $this->artisan('nativephp-mobile-locales:sync-ios', ['--build-path' => $buildDir])
            ->assertSuccessful();

        $this->assertSame(['en', 'pt-BR'], $this->readLocalizationsFrom($simulatorPlist));

        unlink($simulatorPlist);
        rmdir($buildDir);
    }

    public function test_is_idempotent(): void
    {
        $this->writePlist($this->basePlist());

        $this->artisan('nativephp-mobile-locales:sync-ios', ['--path' => $this->plist])->assertSuccessful();
        $first = file_get_contents($this->plist);

        $this->artisan('nativephp-mobile-locales:sync-ios', ['--path' => $this->plist])->assertSuccessful();
        $second = file_get_contents($this->plist);

        $this->assertSame($first, $second);
        $this->assertSame(1, substr_count($second, 'CFBundleLocalizations'));
    }
}

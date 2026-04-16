<?php

namespace Developernauts\NativephpMobileLocales\Commands;

use Developernauts\NativephpMobileLocales\NativephpMobileLocales;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;

class SyncIosLocales extends Command
{
    protected $signature = 'nativephp-mobile-locales:sync-ios
                        {--platform= : Platform passed by NativePHP hooks}
                        {--build-path= : Build path passed by NativePHP hooks}
                        {--plugin-path= : Plugin path passed by NativePHP hooks}
                        {--app-id= : App id passed by NativePHP hooks}
                        {--config= : Config path passed by NativePHP hooks}
                        {--path= : Override the Info.plist path}
                        {--plugins= : Plugin list passed by NativePHP hooks}';

    protected $description = 'Write the configured locales into the iOS Info.plist (CFBundleLocalizations).';

    public function handle(NativephpMobileLocales $locales): int
    {
        $platform = $this->option('platform');

        if ($platform !== null && $platform !== '' && $platform !== 'ios') {
            return self::SUCCESS;
        }

        $path = $this->resolvePlistPath();

        if (! is_file($path)) {
            $this->components->error("Info.plist not found at: {$path}");

            return self::FAILURE;
        }

        $values = $locales->toIOS();

        if ($values === []) {
            $this->components->warn('No locales configured — skipping CFBundleLocalizations update.');

            return self::SUCCESS;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (! @$dom->load($path)) {
            $this->components->error("Failed to parse Info.plist at: {$path}");

            return self::FAILURE;
        }

        $rootDict = (new DOMXPath($dom))->query('/plist/dict')->item(0);

        if (! $rootDict instanceof DOMElement) {
            $this->components->error('Info.plist is missing a root <dict> element.');

            return self::FAILURE;
        }

        $this->setLocalizations($dom, $rootDict, $values);

        $dom->save($path);

        $this->components->info(sprintf(
            'CFBundleLocalizations set to: %s',
            implode(', ', $values)
        ));

        return self::SUCCESS;
    }

    protected function resolvePlistPath(): string
    {
        if ($override = $this->option('path')) {
            return $override;
        }

        if ($buildPath = $this->option('build-path')) {
            return rtrim($buildPath, DIRECTORY_SEPARATOR).'/NativePHP/Info.plist';
        }

        return base_path('nativephp/ios/NativePHP/Info.plist');
    }

    /**
     * @param  array<int, string>  $locales
     */
    protected function setLocalizations(DOMDocument $dom, DOMElement $rootDict, array $locales): void
    {
        $array = $dom->createElement('array');

        foreach ($locales as $locale) {
            $array->appendChild($dom->createElement('string', $locale));
        }

        foreach ($rootDict->childNodes as $node) {
            if (! ($node instanceof DOMElement)
                || $node->nodeName !== 'key'
                || $node->textContent !== 'CFBundleLocalizations'
            ) {
                continue;
            }

            $sibling = $node->nextSibling;

            while ($sibling !== null && ! ($sibling instanceof DOMElement)) {
                $sibling = $sibling->nextSibling;
            }

            if ($sibling instanceof DOMElement) {
                $rootDict->replaceChild($array, $sibling);

                return;
            }

            $rootDict->insertBefore($array, $node->nextSibling);

            return;
        }

        $rootDict->appendChild($dom->createElement('key', 'CFBundleLocalizations'));
        $rootDict->appendChild($array);
    }
}

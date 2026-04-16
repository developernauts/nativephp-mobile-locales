<?php

namespace Developernauts\NativephpMobileLocales;

use InvalidArgumentException;

class NativephpMobileLocales
{
    /** @var array<int, string> */
    protected $locales = [];

    /**
     * @param  array<int, string>  $locales
     */
    public function __construct(array $locales = [])
    {
        foreach ($locales as $locale) {
            $this->add($locale);
        }
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return array_values($this->locales);
    }

    public function add(string $locale): void
    {
        $normalized = $this->normalize($locale);

        if (! in_array($normalized, $this->locales, true)) {
            $this->locales[] = $normalized;
        }
    }

    public function remove(string $locale): void
    {
        $normalized = $this->normalize($locale);

        $this->locales = array_values(array_filter(
            $this->locales,
            static fn (string $existing): bool => $existing !== $normalized
        ));
    }

    public function has(string $locale): bool
    {
        return in_array($this->normalize($locale), $this->locales, true);
    }

    /**
     * Locales formatted for iOS `CFBundleLocalizations` (BCP 47 with hyphen).
     *
     * @return array<int, string>
     */
    public function toIOS(): array
    {
        return $this->all();
    }

    /**
     * Locales formatted as Android resource qualifiers (e.g. `en`, `en-rGB`).
     *
     * @return array<int, string>
     */
    public function toAndroid(): array
    {
        return array_map(static function (string $locale): string {
            if (strpos($locale, '-') === false) {
                return $locale;
            }

            [$language, $region] = explode('-', $locale, 2);

            return $language.'-r'.strtoupper($region);
        }, $this->all());
    }

    protected function normalize(string $locale): string
    {
        $locale = trim(str_replace('_', '-', $locale));

        if ($locale === '') {
            throw new InvalidArgumentException('Locale cannot be empty.');
        }

        $parts = explode('-', $locale);

        if (count($parts) > 2 || ! preg_match('/^[a-zA-Z]{2,3}$/', $parts[0])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid locale "%s". Expected BCP 47 tag like "en" or "en-GB".',
                $locale
            ));
        }

        $language = strtolower($parts[0]);

        if (count($parts) === 1) {
            return $language;
        }

        if (! preg_match('/^[a-zA-Z]{2}$/', $parts[1])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid region subtag in locale "%s". Expected two-letter ISO 3166-1 code.',
                $locale
            ));
        }

        return $language.'-'.strtoupper($parts[1]);
    }
}

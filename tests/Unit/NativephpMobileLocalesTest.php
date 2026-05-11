<?php

namespace Developernauts\NativephpMobileLocales\Tests\Unit;

use Developernauts\NativephpMobileLocales\NativephpMobileLocales;
use Developernauts\NativephpMobileLocales\Tests\TestCase;
use InvalidArgumentException;

class NativephpMobileLocalesTest extends TestCase
{
    public function test_constructor_accepts_locale_array(): void
    {
        $locales = new NativephpMobileLocales(['en', 'fr', 'pt-BR']);

        $this->assertSame(['en', 'fr', 'pt-BR'], $locales->all());
    }

    public function test_add_normalizes_underscores_to_hyphens(): void
    {
        $locales = new NativephpMobileLocales();
        $locales->add('en_GB');

        $this->assertSame(['en-GB'], $locales->all());
    }

    public function test_add_normalizes_case(): void
    {
        $locales = new NativephpMobileLocales();
        $locales->add('EN-gb');

        $this->assertSame(['en-GB'], $locales->all());
    }

    public function test_add_deduplicates_across_case_and_separator_variants(): void
    {
        $locales = new NativephpMobileLocales();
        $locales->add('en-GB');
        $locales->add('en_gb');
        $locales->add('EN-GB');

        $this->assertCount(1, $locales->all());
    }

    public function test_add_throws_on_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Locale cannot be empty.');

        (new NativephpMobileLocales())->add('');
    }

    public function test_add_throws_on_three_part_bcp47_tag(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new NativephpMobileLocales())->add('zh-Hans-CN');
    }

    public function test_add_throws_on_numeric_language_tag(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new NativephpMobileLocales())->add('123');
    }

    public function test_add_throws_on_three_letter_region_subtag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid region subtag');

        (new NativephpMobileLocales())->add('en-GBR');
    }

    public function test_remove_deletes_a_present_locale(): void
    {
        $locales = new NativephpMobileLocales(['en', 'fr']);
        $locales->remove('en');

        $this->assertSame(['fr'], $locales->all());
    }

    public function test_remove_normalizes_before_matching(): void
    {
        $locales = new NativephpMobileLocales(['en-GB']);
        $locales->remove('EN_gb');

        $this->assertSame([], $locales->all());
    }

    public function test_remove_is_no_op_for_absent_locale(): void
    {
        $locales = new NativephpMobileLocales(['en']);
        $locales->remove('fr');

        $this->assertSame(['en'], $locales->all());
    }

    public function test_all_returns_re_indexed_array_after_remove(): void
    {
        $locales = new NativephpMobileLocales(['en', 'fr', 'de']);
        $locales->remove('fr');

        $result = $locales->all();

        $this->assertSame(['en', 'de'], array_values($result));
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
    }

    public function test_has_returns_true_for_present_locale(): void
    {
        $locales = new NativephpMobileLocales(['en-GB']);

        $this->assertTrue($locales->has('en-GB'));
        $this->assertTrue($locales->has('EN_gb'));
    }

    public function test_has_returns_false_for_absent_locale(): void
    {
        $locales = new NativephpMobileLocales(['en']);

        $this->assertFalse($locales->has('fr'));
    }

    public function test_to_ios_returns_bcp47_with_hyphen(): void
    {
        $locales = new NativephpMobileLocales(['en', 'en-GB', 'pt-BR']);

        $this->assertSame(['en', 'en-GB', 'pt-BR'], $locales->toIOS());
    }

    public function test_to_android_converts_region_subtag_to_qualifier_format(): void
    {
        $locales = new NativephpMobileLocales(['en', 'en-GB', 'pt-BR']);

        $this->assertSame(['en', 'en-rGB', 'pt-rBR'], $locales->toAndroid());
    }

    public function test_to_android_leaves_language_only_locales_unchanged(): void
    {
        $locales = new NativephpMobileLocales(['en', 'fr', 'de']);

        $this->assertSame(['en', 'fr', 'de'], $locales->toAndroid());
    }

    public function test_empty_locales_array_is_valid(): void
    {
        $locales = new NativephpMobileLocales([]);

        $this->assertSame([], $locales->all());
        $this->assertSame([], $locales->toIOS());
        $this->assertSame([], $locales->toAndroid());
    }
}

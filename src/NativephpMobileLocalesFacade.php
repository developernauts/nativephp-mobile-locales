<?php

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, string> all()
 * @method static void add(string $locale)
 * @method static void remove(string $locale)
 * @method static bool has(string $locale)
 * @method static array<int, string> toIOS()
 * @method static array<int, string> toAndroid()
 *
 * @see \Developernauts\NativephpMobileLocales\NativephpMobileLocales
 */
class NativephpMobileLocalesFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Developernauts\NativephpMobileLocales\NativephpMobileLocales::class;
    }
}

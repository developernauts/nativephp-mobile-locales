<?php

namespace Developernauts\NativephpMobileLocales\Tests;

use Developernauts\NativephpMobileLocales\NativephpMobileLocalesServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [NativephpMobileLocalesServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('mobile-locales.locales', ['en', 'pt-BR']);
    }
}

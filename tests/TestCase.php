<?php

namespace Tochka\Promises\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Tochka\Promises\PromiseServiceProvider;

class TestCase extends BaseTestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/database/migrations',
        );
    }

    protected function getPackageProviders($app): array
    {
        return [PromiseServiceProvider::class];
    }
}

<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void watch(callable|null $shouldQuitCallback = null, callable|null $shouldPausedCallback = null)
 * @see \Tochka\Promises\Core\PromiseWatcher
 * @codeCoverageIgnore
 */
class PromiseWatcher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void handle(callable|null $shouldQuitCallback = null, callable|null $shouldPausedCallback = null)
 * @method static void clean(callable|null $shouldQuitCallback = null, callable|null $shouldPausedCallback = null)
 * @see \Tochka\Promises\Core\GarbageCollector
 * @codeCoverageIgnore
 */
class GarbageCollector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

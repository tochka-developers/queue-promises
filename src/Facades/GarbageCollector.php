<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void handle()
 * @method static void clean()
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

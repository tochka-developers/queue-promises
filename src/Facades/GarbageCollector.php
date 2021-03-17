<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static handle()
 * @see \Tochka\Promises\Core\GarbageCollector
 */
class GarbageCollector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

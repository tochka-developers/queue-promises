<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static watch()
 * @see \Tochka\Promises\Core\PromiseWatcher
 */
class PromiseWatcher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

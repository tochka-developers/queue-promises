<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;

/**
 * @method static void addDispatcher(DispatcherContract $dispatcher)
 * @method static void dispatch(MayPromised $job)
 * @see \Tochka\Promises\Core\Support\BaseJobDispatcher
 * @codeCoverageIgnore
 */
class BaseJobDispatcher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

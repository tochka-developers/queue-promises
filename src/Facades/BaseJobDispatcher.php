<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;

/**
 * @method static addDispatcher(DispatcherContract $dispatcher)
 * @method static dispatch(MayPromised $job)
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

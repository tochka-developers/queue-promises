<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\Support\BaseJobDispatcherInterface;

/**
 * @api
 * @method static void addDispatcher(DispatcherContract $dispatcher)
 * @method static void dispatch(MayPromised $job)
 * @see BaseJobDispatcherInterface
 * @codeCoverageIgnore
 *
 * @deprecated Inject contract
 */
class BaseJobDispatcher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseJobDispatcherInterface::class;
    }
}

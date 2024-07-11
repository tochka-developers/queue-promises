<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Core\PromiseWatcherInterface;

/**
 * @api
 * @method static void watch(callable|null $shouldQuitCallback = null, callable|null $shouldPausedCallback = null)
 * @see PromiseWatcherInterface
 *
 * @deprecated Inject contract
 */
class PromiseWatcher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PromiseWatcherInterface::class;
    }
}

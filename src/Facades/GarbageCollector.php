<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Core\GarbageCollectorInterface;

/**
 * @api
 * @method static void handle(callable|null $shouldQuitCallback = null, callable|null $shouldPausedCallback = null)
 * @method static void clean(callable|null $shouldQuitCallback = null, callable|null $shouldPausedCallback = null)
 * @see GarbageCollectorInterface
 *
 * @deprecated Inject contract
 */
class GarbageCollector extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GarbageCollectorInterface::class;
    }
}

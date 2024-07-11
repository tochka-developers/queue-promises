<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Core\Support\EventDispatcherInterface;

/**
 * @api
 * @method static void dispatch(PromisedEvent $event)
 * @see EventDispatcherInterface
 * @codeCoverageIgnore
 *
 * @deprecated Inject contract
 */
class EventDispatcher extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EventDispatcherInterface::class;
    }
}

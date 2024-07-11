<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Registry\PromiseEventRegistryInterface;
use Tochka\Promises\Support\WaitEvent;

/**
 * @api
 * @method static save(WaitEvent $waitEvent)
 * @method static Collection<int, WaitEvent> loadByEvent(string $event_name, string $event_unique_id)
 * @method static delete(int $id)
 * @see PromiseEventRegistryInterface
 * @codeCoverageIgnore
 *
 * @deprecated Inject contract
 */
class PromiseEventRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PromiseEventRegistryInterface::class;
    }
}

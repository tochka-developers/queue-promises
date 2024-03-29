<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @deprecated Use \Tochka\Promises\Models\PromiseEvent
 * @method static save(\Tochka\Promises\Support\WaitEvent $waitEvent)
 * @method static \Tochka\Promises\Support\WaitEvent[]|\Illuminate\Support\Collection loadByEvent(string $event_name, string $event_unique_id)
 * @method static delete(int $id)
 * @see \Tochka\Promises\Registry\PromiseEventRegistry
 * @codeCoverageIgnore
 */
class PromiseEventRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

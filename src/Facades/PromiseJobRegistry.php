<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static save(\Tochka\Promises\Core\BaseJob $job)
 * @method static \Tochka\Promises\Core\BaseJob load(int $id)
 * @method static \Tochka\Promises\Core\BaseJob[]|\Illuminate\Support\LazyCollection loadByPromiseIdCursor(int $promise_id)
 * @method static \Tochka\Promises\Core\BaseJob[]|\Illuminate\Support\Collection loadByPromiseId(int $promise_id)
 * @see \Tochka\Promises\Registry\PromiseJobRegistry
 */
class PromiseJobRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

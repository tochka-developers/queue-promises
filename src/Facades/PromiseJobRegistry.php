<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @deprecated Use \Tochka\Promises\Models\PromiseJob
 * @method static save(\Tochka\Promises\Core\BaseJob $job)
 * @method static \Tochka\Promises\Core\BaseJob load(int $id)
 * @method static \Tochka\Promises\Core\BaseJob[]|\Illuminate\Support\LazyCollection loadByPromiseIdCursor(int $promise_id)
 * @method static void loadByPromiseIdChunk(int $promise_id, callable $callback, int $chunk_size = 1000)
 * @method static \Tochka\Promises\Core\BaseJob[]|\Illuminate\Support\Collection loadByPromiseId(int $promise_id)
 * @method static int countByPromiseId(int $promise_id)
 * @method static deleteByPromiseId(int $promise_id)
 * @see \Tochka\Promises\Registry\PromiseJobRegistry
 * @codeCoverageIgnore
 */
class PromiseJobRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

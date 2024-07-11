<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Tochka\Promises\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Registry\PromiseJobRegistryInterface;

/**
 * @api
 * @method static save(BaseJob $job)
 * @method static BaseJob load(int $id)
 * @method static LazyCollection<int, BaseJob> loadByPromiseIdCursor(int $promise_id)
 * @method static void loadByPromiseIdChunk(int $promise_id, callable $callback, int $chunk_size = 1000)
 * @method static Collection<int, BaseJob> loadByPromiseId(int $promise_id)
 * @method static int countByPromiseId(int $promise_id)
 * @method static deleteByPromiseId(int $promise_id)
 * @see PromiseJobRegistryInterface
 *
 * @deprecated Inject contract
 */
class PromiseJobRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PromiseJobRegistryInterface::class;
    }
}

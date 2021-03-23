<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @deprecated Use \Tochka\Promises\Models\Promise
 * @method static save(\Tochka\Promises\Core\BasePromise $promise)
 * @method static \Tochka\Promises\Core\BasePromise load(int $id)
 * @method static \Tochka\Promises\Core\BasePromise[]|\Illuminate\Support\LazyCollection loadAllCursor()
 * @method static void loadAllChunk(callable $callback, int $chunk_size = 1000)
 * @method static \Tochka\Promises\Core\BasePromise[]|\Illuminate\Support\LazyCollection loadInStatesCursor(array $states)
 * @method static void loadInStatesChunk(array $states, callable $callback, int $chunk_size = 1000)
 * @method static delete(int $id)
 * @see \Tochka\Promises\Registry\PromiseRegistry
 * @codeCoverageIgnore
 */
class PromiseRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

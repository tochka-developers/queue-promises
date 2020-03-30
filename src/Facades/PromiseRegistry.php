<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static save(\Tochka\Promises\Core\BasePromise $promise)
 * @method static \Tochka\Promises\Core\BasePromise load(int $id)
 * @method static \Tochka\Promises\Core\BasePromise[]|\Illuminate\Support\LazyCollection loadAllCursor()
 * @method static \Tochka\Promises\Core\BasePromise[]|\Illuminate\Support\LazyCollection loadInStatesCursor(array $states)
 * @method static delete(int $id)
 * @see \Tochka\Promises\Registry\PromiseRegistry
 */
class PromiseRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

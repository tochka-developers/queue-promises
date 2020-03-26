<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static save(\Tochka\Promises\BasePromise $promise)
 * @method static \Tochka\Promises\BasePromise load(int $id)
 * @see \Tochka\Promises\Registry\PromiseRegistry
 */
class PromiseRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

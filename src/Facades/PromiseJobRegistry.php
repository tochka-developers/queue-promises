<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static save(\Tochka\Promises\BaseJob $job)
 * @method static \Tochka\Promises\BaseJob load(int $id)
 * @method static \Tochka\Promises\BaseJob[] loadByPromiseId(int $promise_id)
 * @see \Tochka\Promises\Registry\PromiseJobRegistry
 */
class PromiseJobRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

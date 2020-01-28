<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;

class Promise extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

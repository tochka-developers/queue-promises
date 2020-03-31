<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * @method static array getSerializedConditions(array $conditions)
 * @method static array getUnSerializedConditions(array $conditions)
 * @method static string jsonSerialize(mixed $value)
 * @method static mixed jsonUnSerialize(string $value)
 * @see \Tochka\Promises\Core\Support\Serializer
 */
class Serializer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }
}

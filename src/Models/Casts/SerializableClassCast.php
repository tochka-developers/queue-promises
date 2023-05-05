<?php

namespace Tochka\Promises\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;

/**
 * @template-implements CastsAttributes<object, string>
 */
class SerializableClassCast implements CastsAttributes
{
    /**
     * @throws \JsonException
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        $castedObject = unserialize(
            json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            ['allowed_classes' => true]
        );

        if ($castedObject instanceof \__PHP_Incomplete_Class) {
            throw new IncorrectResolvingClass(
                'Unknown class after deserialization. Most likely the serialized class no longer exists.'
            );
        }

        return $castedObject;
    }

    /**
     * @throws \JsonException
     */
    public function set($model, string $key, $value, array $attributes): array
    {
        return [
            $key => $value === null ? null : json_encode(serialize($value), JSON_THROW_ON_ERROR),
        ];
    }
}

<?php

namespace Tochka\Promises\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;

class SerializableClassCast implements CastsAttributes
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param mixed                               $value
     * @param array                               $attributes
     *
     * @return mixed
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
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param mixed                               $value
     * @param array                               $attributes
     *
     * @return array
     * @throws \JsonException
     */
    public function set($model, string $key, $value, array $attributes): array
    {
        return [
            $key => $value === null ? null : json_encode(serialize($value), JSON_THROW_ON_ERROR),
        ];
    }
}

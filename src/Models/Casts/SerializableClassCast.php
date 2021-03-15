<?php

namespace Tochka\Promises\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

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
        return $value === null ? $value : unserialize(
            json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            ['allowed_classes' => true]
        );
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
    public function set($model, string $key, $value, array $attributes)
    {
        return [
            $key => $value === null ? null : json_encode(serialize($value), JSON_THROW_ON_ERROR, 512),
        ];
    }
}

<?php

namespace Tochka\Promises\DTO;

use Tochka\Promises\Exceptions\IncorrectResolvingClass;

trait CastObject
{
    /**
     * @throws \JsonException
     */
    private function castToObject(?string $value): ?object
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
    private function castFromObject(?object $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(serialize($value), JSON_THROW_ON_ERROR);
    }
}

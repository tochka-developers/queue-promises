<?php

namespace Tochka\Promises\DTO;

use Tochka\Promises\Core\Support\ConditionTransition;

trait CastConditions
{
    /**
     * @throws \JsonException
     */
    private function castToConditions(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        return array_map(
            static function ($conditionTransition) {
                return ConditionTransition::fromArray($conditionTransition);
            },
            json_decode($value, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @throws \JsonException
     */
    private function castFromConditions(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(
            array_map(
                static function (ConditionTransition $conditionTransition) {
                    return $conditionTransition->toArray();
                },
                $value
            ),
            JSON_THROW_ON_ERROR
        );
    }
}

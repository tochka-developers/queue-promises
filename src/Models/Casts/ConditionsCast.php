<?php

namespace Tochka\Promises\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Tochka\Promises\Core\Support\ConditionTransition;

/**
 * @template-implements CastsAttributes<array<ConditionTransition>, string>
 */
class ConditionsCast implements CastsAttributes
{
    /**
     * @throws \JsonException
     */
    public function get($model, string $key, $value, array $attributes): array
    {
        if ($value === null) {
            return [];
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
    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value === null) {
            return [];
        }

        return [
            $key => json_encode(
                array_map(
                    static function (ConditionTransition $conditionTransition) {
                        return $conditionTransition->toArray();
                    },
                    $value
                ),
                JSON_THROW_ON_ERROR
            ),
        ];
    }
}

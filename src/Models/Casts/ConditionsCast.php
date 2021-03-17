<?php

namespace Tochka\Promises\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Tochka\Promises\Core\Support\ConditionTransition;

class ConditionsCast implements CastsAttributes
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param mixed                               $value
     * @param array                               $attributes
     *
     * @return array
     * @throws \JsonException
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($model, string $key, $value, array $attributes): array
    {
        return array_map(
            static function ($conditionTransition) {
                return ConditionTransition::fromArray($conditionTransition);
            },
            json_decode($value, true, 512, JSON_THROW_ON_ERROR)
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
     * @noinspection PhpMissingParamTypeInspection
     */
    public function set($model, string $key, $value, array $attributes): array
    {
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

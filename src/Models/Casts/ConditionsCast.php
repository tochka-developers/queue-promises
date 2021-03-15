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
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return array_map(
            static function ($conditionTransition) {
                return ConditionTransition::fromArray($conditionTransition);
            },
            $value
        );
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string                              $key
     * @param mixed                               $value
     * @param array                               $attributes
     *
     * @return array
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return [
            $key => array_map(
                static function (ConditionTransition $conditionTransition) {
                    return $conditionTransition->toArray();
                },
                $value
            ),
        ];
    }
}

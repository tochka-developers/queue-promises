<?php

namespace Tochka\Promises\Registry;

use Tochka\Promises\Core\Support\ConditionTransition;

trait SerializeConditions
{
    private function getSerializedConditions(array $conditions): array
    {
        return array_map(static function (ConditionTransition $conditionTransition) {
            return $conditionTransition->toArray();
        }, $conditions);
    }

    private function getUnSerializedConditions(array $conditions): array
    {
        return array_map(static function ($conditionTransition) {
            return ConditionTransition::fromArray($conditionTransition);
        }, $conditions);
    }
}
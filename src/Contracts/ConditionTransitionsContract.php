<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\Support\ConditionTransition;

interface ConditionTransitionsContract
{
    /**
     * @param \Tochka\Promises\Core\Support\ConditionTransition $conditionTransition
     */
    public function addCondition(ConditionTransition $conditionTransition): void;

    /**
     * @return \Tochka\Promises\Core\Support\ConditionTransition[]
     */
    public function getConditions(): array;
}
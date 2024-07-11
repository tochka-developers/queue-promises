<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\Support\ConditionTransition;

/**
 * @api
 */
interface ConditionTransitionsContract
{
    public function addCondition(ConditionTransition $conditionTransition): void;

    public function getConditions(): array;
}

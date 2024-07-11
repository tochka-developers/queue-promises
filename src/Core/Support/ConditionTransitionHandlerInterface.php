<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BasePromise;

interface ConditionTransitionHandlerInterface
{
    /**
     * @return array<ConditionTransition>
     */
    public function getConditionsForState(
        StatesContract $state,
        ConditionTransitionsContract $conditionTransitions,
    ): array;

    /**
     * @param array<ConditionTransition> $conditionTransitions
     */
    public function getTransitionForConditions(array $conditionTransitions, BasePromise $promise): ?ConditionTransition;

    public function checkConditionAndApplyTransition(
        StatesContract $statesInstance,
        ConditionTransitionsContract $conditionTransitionsInstance,
        BasePromise $basePromise,
    ): bool;
}

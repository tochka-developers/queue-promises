<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BasePromise;

class ConditionTransitionHandler implements ConditionTransitionHandlerInterface
{
    public function getConditionsForState(
        StatesContract $state,
        ConditionTransitionsContract $conditionTransitions,
    ): array {
        $conditions = $conditionTransitions->getConditions();

        return array_filter(
            $conditions,
            static function (ConditionTransition $conditionTransition) use ($state) {
                return $conditionTransition->getFromState()->is($state->getState());
            },
        );
    }

    public function getTransitionForConditions(array $conditionTransitions, BasePromise $promise): ?ConditionTransition
    {
        foreach ($conditionTransitions as $conditionTransition) {
            $condition = $conditionTransition->getCondition();
            if ($condition->condition($promise)) {
                return $conditionTransition;
            }
        }

        return null;
    }

    public function checkConditionAndApplyTransition(
        StatesContract $statesInstance,
        ConditionTransitionsContract $conditionTransitionsInstance,
        BasePromise $basePromise,
    ): bool {
        $conditions = $this->getConditionsForState($statesInstance, $conditionTransitionsInstance);
        $transition = $this->getTransitionForConditions($conditions, $basePromise);

        if ($transition !== null) {
            $statesInstance->setState($transition->getToState());

            return true;
        }

        return false;
    }
}

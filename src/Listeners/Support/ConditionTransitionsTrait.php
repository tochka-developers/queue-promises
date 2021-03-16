<?php

namespace Tochka\Promises\Listeners\Support;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;

trait ConditionTransitionsTrait
{
    /**
     * @param StatesContract               $state
     * @param ConditionTransitionsContract $conditionTransitions
     *
     * @return array<ConditionTransition>
     */
    public function getConditionsForState(
        StatesContract $state,
        ConditionTransitionsContract $conditionTransitions
    ): array {
        $conditions = $conditionTransitions->getConditions();

        return array_filter(
            $conditions,
            static function (ConditionTransition $conditionTransition) use ($state) {
                return $conditionTransition->getFromState()->is($state->getState());
            }
        );
    }

    /**
     * @param array<ConditionTransition> $conditionTransitions
     * @param BasePromise                $promise
     *
     * @return ConditionTransition|null
     */
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
}

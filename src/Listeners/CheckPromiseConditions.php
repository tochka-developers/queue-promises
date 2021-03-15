<?php

namespace Tochka\Promises\Listeners;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Models\Promise;

class CheckPromiseConditions
{
    public function handle(PromiseJobStateChanged $event): void
    {
        $promise = Promise::find($event->getPromiseJob()->getPromiseId());
        if ($promise === null) {
            return;
        }

        $basePromise = $promise->getBasePromise();

        $conditions = $this->getConditionsForState($basePromise, $basePromise);
        $transition = $this->getTransitionForConditions($conditions, $basePromise);
        if ($transition) {
            $basePromise->setState($transition->getToState());
            Promise::saveBasePromise($basePromise);
        }
    }

    private function getConditionsForState(
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
     * @param ConditionTransition[]             $conditionTransitions
     * @param \Tochka\Promises\Core\BasePromise $promise
     *
     * @return \Tochka\Promises\Core\Support\ConditionTransition
     */
    private function getTransitionForConditions(array $conditionTransitions, BasePromise $promise): ?ConditionTransition
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

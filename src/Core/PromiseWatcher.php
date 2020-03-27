<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Facades\PromiseJobRegistry;
use Tochka\Promises\Facades\PromiseRegistry;

class PromiseWatcher
{
    private $iteration_time = 5;

    public function watch(): void
    {
        while (true) {
            $time = microtime(true);

            foreach (PromiseRegistry::loadAllCursor() as $promise) {
                $conditions = $this->getConditionsForState($promise, $promise);
                $transition = $this->getTransitionForConditions($conditions, $promise);
                if ($transition) {
                    $promise->setState($transition->getToState());
                    PromiseRegistry::save($promise);
                }

                foreach (PromiseJobRegistry::loadByPromiseIdCursor($promise->getPromiseId()) as $job) {
                    $conditions = $this->getConditionsForState($job, $job);
                    $transition = $this->getTransitionForConditions($conditions, $promise);
                    if ($transition) {
                        $job->setState($transition->getToState());
                        PromiseJobRegistry::save($job);
                    }
                }
            }

            $sleep_time = floor($this->iteration_time - (microtime(true) - $time));

            if ($sleep_time < 1) {
                $sleep_time = 1;
            }

            sleep($sleep_time);
        }
    }

    private function getConditionsForState(
        StatesContract $state,
        ConditionTransitionsContract $conditionTransitions
    ): array {
        $conditions = $conditionTransitions->getConditions();

        return array_filter($conditions, static function (ConditionTransition $conditionTransition) use ($state) {
            return $conditionTransition->getFromState()->is($state->getState());
        });
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
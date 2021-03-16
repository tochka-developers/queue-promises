<?php

namespace Tochka\Promises\Core;

use Illuminate\Support\Collection;
use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class PromiseWatcher
{
    private int $iteration_time = 5;

    public function watch(): void
    {
        while (true) {
            $time = microtime(true);

            Promise::inStates([StateEnum::WAITING(), StateEnum::RUNNING()])
                ->with('jobs')
                ->chunk(
                    100,
                    function (Collection $promises) {
                        /** @var Promise $promise */
                        foreach ($promises as $promise) {
                            try {
                                $basePromise = $promise->getBasePromise();
                                $conditions = $this->getConditionsForState($basePromise, $basePromise);
                                $transition = $this->getTransitionForConditions($conditions, $basePromise);
                                if ($transition) {
                                    $basePromise->setState($transition->getToState());
                                    Promise::saveBasePromise($basePromise);
                                }

                                foreach ($promise->jobs as $job) {
                                    $baseJob = $job->getBaseJob();

                                    $conditions = $this->getConditionsForState($baseJob, $baseJob);
                                    $transition = $this->getTransitionForConditions($conditions, $basePromise);
                                    if ($transition) {
                                        $baseJob->setState($transition->getToState());
                                        PromiseJob::saveBaseJob($baseJob);
                                    }
                                }
                            } catch (\Exception $e) {
                                report($e);
                            }
                        }
                    }
                );

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

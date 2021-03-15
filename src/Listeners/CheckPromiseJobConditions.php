<?php

namespace Tochka\Promises\Listeners;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class CheckPromiseJobConditions
{
    public function handle($event): void
    {
        if ($event instanceof PromiseStateChanged) {
            $promise = $event->getPromise();
            $promisedJob = null;
        } elseif ($event instanceof PromiseJobStateChanged) {
            $promiseId = $event->getPromiseJob()->getPromiseId();
            $promiseModel = Promise::find($promiseId);
            if ($promiseModel === null) {
                return;
            }
            $promise = $promiseModel->getBasePromise();
            $promisedJob = $event->getPromiseJob();
        } else {
            return;
        }

        PromiseJob::byPromise($promise->getPromiseId())
            ->chunk(
                100,
                function (BaseJob $job) use ($promise, $promisedJob) {
                    if ($promisedJob !== null && $job->getJobId() === $promisedJob->getJobId()) {
                        return;
                    }

                    $conditions = $this->getConditionsForState($job, $job);
                    $transition = $this->getTransitionForConditions($conditions, $promise);
                    if ($transition) {
                        $job->setState($transition->getToState());
                        PromiseJob::saveBaseJob($job);
                    }
                }
            );
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

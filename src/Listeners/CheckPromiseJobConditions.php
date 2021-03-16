<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Support\Facades\DB;
use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Listeners\Support\ConditionTransitionsTrait;
use Tochka\Promises\Models\PromiseJob;

class CheckPromiseJobConditions
{
    use ConditionTransitionsTrait;

    public function handle(StateChangedContract $event): void
    {
        if ($event instanceof PromiseStateChanged) {
            $promise = $event->getPromise();
            $promisedJob = null;
        } elseif ($event instanceof PromiseJobStateChanged) {
            $promiseModel = $event->getPromiseJob()->getAttachedModel()->promise;
            if ($promiseModel === null) {
                return;
            }
            $promise = $promiseModel->getBasePromise();
            $promisedJob = $event->getPromiseJob();
        } else {
            return;
        }

        DB::transaction(
            function () use ($promise, $promisedJob) {
                /** @var array<PromiseJob> $jobs */
                $jobs = PromiseJob::byPromise($promise->getPromiseId())->lock()->get();
                foreach ($jobs as $job) {
                    $baseJob = $job->getBaseJob();
                    if ($promisedJob !== null && $baseJob->getJobId() === $promisedJob->getJobId()) {
                        continue;
                    }

                    $conditions = $this->getConditionsForState($baseJob, $baseJob);
                    $transition = $this->getTransitionForConditions($conditions, $promise);

                    if ($transition) {
                        $baseJob->setState($transition->getToState());
                        PromiseJob::saveBaseJob($baseJob);
                    }
                }
            },
            5
        );
    }
}

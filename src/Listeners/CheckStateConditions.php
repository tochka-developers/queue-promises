<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Support\Facades\DB;
use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Core\Support\ConditionTransitionsTrait;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class CheckStateConditions
{
    use ConditionTransitionsTrait;

    public function handle(StateChangedContract $event): void
    {
        if ($event instanceof PromiseStateChanged) {
            $basePromise = $event->getPromise();
            $promisedJob = null;
        } elseif ($event instanceof PromiseJobStateChanged) {
            $promiseModel = $event->getPromiseJob()->getAttachedModel()->promise;
            if ($promiseModel === null) {
                return;
            }
            $basePromise = $promiseModel->getBasePromise();
            $promisedJob = $event->getPromiseJob();
        } else {
            return;
        }

        DB::transaction(
            function () use ($basePromise, $promisedJob) {
                /** @var array<PromiseJob> $jobs */
                $jobs = PromiseJob::byPromise($basePromise->getPromiseId())->lock()->get();
                foreach ($jobs as $job) {
                    $baseJob = $job->getBaseJob();
                    if ($promisedJob !== null && $baseJob->getJobId() === $promisedJob->getJobId()) {
                        continue;
                    }

                    $conditions = $this->getConditionsForState($baseJob, $baseJob);
                    $transition = $this->getTransitionForConditions($conditions, $basePromise);

                    if ($transition) {
                        $baseJob->setState($transition->getToState());
                        PromiseJob::saveBaseJob($baseJob);
                    }
                }

                $conditions = $this->getConditionsForState($basePromise, $basePromise);
                $transition = $this->getTransitionForConditions($conditions, $basePromise);
                if ($transition) {
                    $basePromise->setState($transition->getToState());
                    Promise::saveBasePromise($basePromise);
                }
            },
            5
        );
    }
}

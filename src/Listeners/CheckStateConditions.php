<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Support\Facades\DB;
use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Facades\ConditionTransitionHandler;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class CheckStateConditions
{
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

                    if (ConditionTransitionHandler::checkConditionAndApplyTransition($baseJob, $baseJob, $basePromise)) {
                        PromiseJob::saveBaseJob($baseJob);
                    }
                }

                if (ConditionTransitionHandler::checkConditionAndApplyTransition($basePromise, $basePromise, $basePromise)) {
                    Promise::saveBasePromise($basePromise);
                }
            },
            5
        );
    }
}

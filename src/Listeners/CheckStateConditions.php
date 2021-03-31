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

        $jobIds = PromiseJob::byPromise($basePromise->getPromiseId())
            ->get()
            ->pluck('id');

        foreach ($jobIds as $jobId) {
            DB::transaction(
                function () use ($basePromise, $promisedJob, $jobId) {
                    /** @var PromiseJob $currentJob */
                    $currentJob = PromiseJob::lockForUpdate()->find($jobId);
                    $baseJob = $currentJob->getBaseJob();
                    if ($promisedJob !== null && $baseJob->getJobId() === $promisedJob->getJobId()) {
                        return;
                    }

                    if (ConditionTransitionHandler::checkConditionAndApplyTransition(
                        $baseJob,
                        $baseJob,
                        $basePromise
                    )) {
                        PromiseJob::saveBaseJob($baseJob);
                    }
                },
                3
            );
        }

        DB::transaction(
            function () use ($basePromise) {
                /** @var Promise $promise */
                $promise = Promise::lockForUpdate()->find($basePromise->getPromiseId());
                $basePromise = $promise->getBasePromise();

                if (ConditionTransitionHandler::checkConditionAndApplyTransition(
                    $basePromise,
                    $basePromise,
                    $basePromise
                )) {
                    Promise::saveBasePromise($basePromise);
                }
            },
            3
        );
    }
}

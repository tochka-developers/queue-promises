<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Support\Facades\DB;
use Tochka\Promises\Contracts\NestedEventContract;
use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Facades\ConditionTransitionHandler;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class CheckStateConditions
{
    public function handle(StateChangedContract $event): void
    {
        if ($event instanceof NestedEventContract && $event->isNested()) {
            return;
        }

        if ($event instanceof PromiseStateChanged) {
            $basePromise = $event->getPromise();
            $currentJobId = null;
        } elseif ($event instanceof PromiseJobStateChanged) {
            $promiseModel = $event->getPromiseJob()->getAttachedModel()->promise;
            if ($promiseModel === null) {
                return;
            }
            $basePromise = $promiseModel->getBasePromise();
            $currentJobId = $event->getPromiseJob()->getJobId();
        } else {
            return;
        }

        $jobIds = PromiseJob::byPromise($basePromise->getPromiseId())
            ->orderBy('id')
            ->get()
            ->pluck('id')
            ->toArray();

        $this->checkStateConditions($basePromise, $jobIds, $currentJobId);
    }

    private function checkStateConditions(BasePromise $basePromise, array $jobIds, ?int $currentJobId = null): void
    {
        $stateChanges = false;

        foreach ($jobIds as $jobId) {
            DB::transaction(
                function () use ($basePromise, $currentJobId, $jobId, &$stateChanges) {
                    /** @var PromiseJob $currentJob */
                    $currentJob = PromiseJob::lockForUpdate()->find($jobId);
                    $baseJob = $currentJob->getBaseJob();
                    if ($currentJobId !== null && $baseJob->getJobId() === $currentJobId) {
                        return;
                    }

                    if (ConditionTransitionHandler::checkConditionAndApplyTransition(
                        $baseJob,
                        $baseJob,
                        $basePromise
                    )) {
                        $stateChanges = true;
                        // включаем вложенные события, чтобы не обрабатывать их этим слушателем
                        $baseJob->getAttachedModel()->setNestedEvents(true);
                        PromiseJob::saveBaseJob($baseJob);
                    }
                },
                3
            );
        }

        DB::transaction(
            function () use ($basePromise, &$stateChanges) {
                /** @var Promise $promise */
                $promise = Promise::lockForUpdate()->find($basePromise->getPromiseId());
                $basePromise = $promise->getBasePromise();

                if (ConditionTransitionHandler::checkConditionAndApplyTransition(
                    $basePromise,
                    $basePromise,
                    $basePromise
                )) {
                    $stateChanges = true;
                    // включаем вложенные события, чтобы не обрабатывать их этим слушателем
                    $basePromise->getAttachedModel()->setNestedEvents(true);
                    Promise::saveBasePromise($basePromise);
                }
            },
            3
        );

        // если были какие-либо изменения состояний в джобах или промисе - то еще раз чекнем все условия переходов
        if ($stateChanges) {
            $this->checkStateConditions($basePromise, $jobIds);
        }
    }
}

<?php

namespace Tochka\Promises\Core;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tochka\Promises\Core\Support\DaemonWorker;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\ConditionTransitionHandler;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class PromiseWatcher
{
    use DaemonWorker;

    private string $promisesTable;
    private string $promiseJobsTable;
    private int $promiseChunkSize;
    private int $jobsChunkSize;

    public function __construct(
        int $sleepTime,
        string $promisesTable,
        string $promiseJobsTable,
        int $promiseChunkSize = 100,
        int $jobsChunkSize = 500,
    )
    {
        $this->sleepTime = $sleepTime;
        $this->promisesTable = $promisesTable;
        $this->promiseJobsTable = $promiseJobsTable;
        $this->promiseChunkSize = $promiseChunkSize;
        $this->jobsChunkSize = $jobsChunkSize;

        $this->lastIteration = Carbon::minValue();
    }

    public function watch(): void
    {
        $this->daemon(function () {
            $this->watchIteration();
        });
    }

    public function watchIteration(): void
    {
        while (true) {
            $promises = DB::table($this->promisesTable)
                ->whereIn('state', [StateEnum::WAITING, StateEnum::RUNNING])
                ->where('watch_at', '<', Carbon::now())
                ->limit($this->promiseChunkSize)
                ->pluck('id')
                ->all();

            if (empty($promises)) {
                return;
            }

            $this->handlePromiseChunks($promises);

            $this->sleep(0.05);
        }
    }

    /**
     * @param array<int> $promiseIds
     * @return bool
     */
    private function handlePromiseChunks(array $promiseIds): bool
    {
        if ($this->paused() || $this->shouldQuit()) {
            return false;
        }

        foreach ($promiseIds as $promise) {
            try {
                $this->checkPromiseConditions($promise);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return true;
    }

    public function checkPromiseConditions(int $promiseId): void
    {
        $basePromise = DB::transaction(
            function () use ($promiseId) {
                /** @var Promise|null $lockedPromise */
                $lockedPromise = Promise::lockForUpdate()->find($promiseId);
                if ($lockedPromise === null) {
                    return null;
                }

                $basePromise = $lockedPromise->getBasePromise();
                if ($basePromise->getTimeoutAt() <= Carbon::now()) {
                    $basePromise->setState(StateEnum::TIMEOUT());
                } else {
                    ConditionTransitionHandler::checkConditionAndApplyTransition(
                        $basePromise,
                        $basePromise,
                        $basePromise
                    );
                }

                $nextWatch = Carbon::now()->addSeconds(watcher_watch_timeout());
                if ($nextWatch > Carbon::now()) {
                    $basePromise->setWatchAt($nextWatch);
                }

                Promise::saveBasePromise($basePromise);

                return $basePromise;
            },
            3
        );

        if ($basePromise === null) {
            return;
        }

        $jobsIds = DB::table($this->promiseJobsTable)
            ->select(['id'])
            ->where('promise_id', $promiseId)
            ->pluck('id')
            ->all();

        foreach ($jobsIds as $jobId) {
            $this->checkJobConditions($jobId, $basePromise);
        }
    }

    public function checkJobConditions(int $jobId, BasePromise $basePromise): void
    {
        DB::transaction(
            function () use ($jobId, $basePromise) {
                /** @var PromiseJob|null $lockedJob */
                $lockedJob = PromiseJob::lockForUpdate()->find($jobId);
                if ($lockedJob === null) {
                    return;
                }
                $baseJob = $lockedJob->getBaseJob();

                if (ConditionTransitionHandler::checkConditionAndApplyTransition($baseJob, $baseJob, $basePromise)) {
                    PromiseJob::saveBaseJob($baseJob);
                }
            },
            3
        );
    }
}

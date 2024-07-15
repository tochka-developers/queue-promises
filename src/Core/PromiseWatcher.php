<?php

namespace Tochka\Promises\Core;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tochka\Promises\Core\Support\ConditionTransitionHandlerInterface;
use Tochka\Promises\Core\Support\DaemonWorker;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class PromiseWatcher implements PromiseWatcherInterface
{
    use DaemonWorker;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private readonly ConditionTransitionHandlerInterface $conditionTransitionHandler,
        int $sleepTime,
        private readonly string $promisesTable,
        private readonly string $promiseJobsTable,
        private readonly int $promiseChunkSize = 100,
    ) {
        $this->sleepTime = $sleepTime;
        $this->lastIteration = Carbon::minValue();
    }

    public function watch(?callable $shouldQuitCallback = null, ?callable $shouldPausedCallback = null): void
    {
        if ($shouldQuitCallback === null) {
            $shouldQuitCallback = fn(): bool => false;
        }

        if ($shouldPausedCallback === null) {
            $shouldPausedCallback = fn(): bool => false;
        }

        $this->daemon(function () use ($shouldQuitCallback, $shouldPausedCallback) {
            $this->watchIteration($shouldQuitCallback, $shouldPausedCallback);
        }, $shouldQuitCallback, $shouldPausedCallback);
    }

    public function watchIteration(callable $shouldQuitCallback, callable $shouldPausedCallback): void
    {
        while (!$shouldQuitCallback() && !$shouldPausedCallback()) {
            $promises = DB::table($this->promisesTable)
                ->whereIn('state', [StateEnum::WAITING, StateEnum::RUNNING])
                ->where('watch_at', '<', Carbon::now())
                ->limit($this->promiseChunkSize)
                ->pluck('id')
                ->all();

            if (empty($promises)) {
                return;
            }

            $this->handlePromiseChunks($promises, $shouldQuitCallback, $shouldPausedCallback);

            $this->sleep(0.05);
        }
    }

    /**
     * @param array<int> $promiseIds
     * @param callable(): bool $shouldQuitCallback
     * @param callable(): bool $shouldPausedCallback
     */
    private function handlePromiseChunks(
        array $promiseIds,
        callable $shouldQuitCallback,
        callable $shouldPausedCallback,
    ): void {
        foreach ($promiseIds as $promise) {
            if ($shouldQuitCallback() || $shouldPausedCallback()) {
                return;
            }

            try {
                $this->checkPromiseConditions($promise);
            } catch (\Throwable $e) {
                report($e);
            }
        }
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

                try {
                    $basePromise = $lockedPromise->getBasePromise();
                    if ($basePromise->getTimeoutAt() <= Carbon::now()) {
                        $basePromise->setState(StateEnum::TIMEOUT());
                    } else {
                        $this->conditionTransitionHandler->checkConditionAndApplyTransition(
                            $basePromise,
                            $basePromise,
                            $basePromise,
                        );
                    }

                    $nextWatch = Carbon::now()->addSeconds(watcher_watch_timeout());
                    if ($nextWatch > $basePromise->getTimeoutAt()) {
                        $nextWatch = $basePromise->getTimeoutAt();
                    }
                    if ($nextWatch > Carbon::now()) {
                        $basePromise->setWatchAt($nextWatch);
                    }

                    Promise::saveBasePromise($basePromise);

                    return $basePromise;
                } catch (IncorrectResolvingClass $e) {
                    $lockedPromise->state = StateEnum::INCORRECT();
                    $lockedPromise->save();

                    DB::table($this->promiseJobsTable)
                        ->where('promise_id', $promiseId)
                        ->update(['state' => StateEnum::CANCELED]);

                    report($e);

                    return null;
                }
            },
            3,
        );

        if ($basePromise === null) {
            return;
        }

        $jobsIds = DB::table($this->promiseJobsTable)
            ->select(['id'])
            ->where('promise_id', $promiseId)
            ->pluck('id')
            ->all();

        $hasIncorrectResolveError = false;
        foreach ($jobsIds as $jobId) {
            try {
                $this->checkJobConditions($jobId, $basePromise);
            } catch (IncorrectResolvingClass) {
                $hasIncorrectResolveError = true;
            }
        }

        if ($hasIncorrectResolveError) {
            DB::table($this->promisesTable)
                ->where('id', $promiseId)
                ->update(['state' => StateEnum::INCORRECT]);
        }
    }

    public function checkJobConditions(int $jobId, BasePromise $basePromise): void
    {
        DB::transaction(
            function () use ($jobId, $basePromise) {
                /** @var PromiseJob|null $lockedJob */
                $lockedJob = PromiseJob::query()->lockForUpdate()->find($jobId);
                if ($lockedJob === null) {
                    return;
                }

                try {
                    $baseJob = $lockedJob->getBaseJob();

                    if ($this->conditionTransitionHandler->checkConditionAndApplyTransition(
                        $baseJob,
                        $baseJob,
                        $basePromise,
                    )) {
                        PromiseJob::saveBaseJob($baseJob);
                    }
                } catch (IncorrectResolvingClass $e) {
                    $lockedJob->state = StateEnum::INCORRECT();
                    $lockedJob->save();

                    throw $e;
                }
            },
            3,
        );
    }
}

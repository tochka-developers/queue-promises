<?php

namespace Tochka\Promises\Core;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\ConditionTransitionHandler;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class PromiseWatcher
{
    private Carbon $iterationTime;
    private int $minSleepTime = 100000;

    /**
     * @codeCoverageIgnore
     */
    public function watch(): void
    {
        while (true) {
            $this->startTime();
            $this->watchIteration();
            $this->calcDiffAndSleep();
        }
    }

    public function watchIteration(): void
    {
        Promise::inStates([StateEnum::WAITING(), StateEnum::RUNNING()])
            ->forWatch()
            ->with('jobs')
            ->chunk(
                100,
                $this->getChunkHandleCallback()
            );
    }

    protected function getChunkHandleCallback(): callable
    {
        return function (Collection $promises) {
            /** @var Promise $promise */
            foreach ($promises as $promise) {
                try {
                    $this->checkPromiseConditions($promise);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        };
    }

    public function startTime(): void
    {
        $this->iterationTime = Carbon::now();
    }

    public function calcDiffAndSleep(): void
    {
        $sleepTime = Carbon::now()->diffInMicroseconds($this->iterationTime);

        if ($sleepTime < $this->minSleepTime) {
            $sleepTime = $this->minSleepTime;
        }

        $this->sleep($sleepTime);
    }

    /**
     * @param int $sleepTime
     *
     * @codeCoverageIgnore
     */
    protected function sleep(int $sleepTime): void
    {
        usleep($sleepTime);
    }

    public function checkPromiseConditions(Promise $promise): void
    {
        $basePromise = $promise->getBasePromise();
        if ($basePromise->getTimeoutAt() <= Carbon::now()) {
            $basePromise->setState(StateEnum::TIMEOUT());
        } else {
            ConditionTransitionHandler::checkConditionAndApplyTransition($basePromise, $basePromise, $basePromise);
        }

        $nextWatch = Carbon::now()->addSeconds(watcher_watch_timeout());
        if ($nextWatch > Carbon::now()) {
            $basePromise->setWatchAt($nextWatch);
        }

        Promise::saveBasePromise($basePromise);

        foreach ($promise->jobs as $job) {
            $this->checkJobConditions($job, $basePromise);
        }
    }

    public function checkJobConditions(PromiseJob $promiseJob, BasePromise $basePromise): void
    {
        $baseJob = $promiseJob->getBaseJob();

        if (ConditionTransitionHandler::checkConditionAndApplyTransition($baseJob, $baseJob, $basePromise)) {
            PromiseJob::saveBaseJob($baseJob);
        }
    }
}

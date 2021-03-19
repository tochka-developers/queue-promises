<?php

namespace Tochka\Promises\Core;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tochka\Promises\Core\Support\ConditionTransitionsTrait;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class PromiseWatcher
{
    use ConditionTransitionsTrait;

    private int $iterationTime = 0;
    private int $maxSleepTime = 2000000;

    /**
     * @codeCoverageIgnore
     */
    public function watch(): void
    {
        while (true) {
            $this->startTime();
            $this->watchIteration();
            $this->sleep();
        }
    }

    public function watchIteration(): void
    {
        Promise::inStates([StateEnum::WAITING(), StateEnum::RUNNING()])
            ->forWatch()
            ->with('jobs')
            ->chunk(
                100,
                function (Collection $promises) {
                    /** @var Promise $promise */
                    foreach ($promises as $promise) {
                        try {
                            $this->checkPromiseConditions($promise);
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }
                }
            );
    }

    public function startTime(): void
    {
        $this->iterationTime = floor(microtime(true) * 1000000);
    }

    public function sleep(): void
    {
        $sleep_time = floor($this->maxSleepTime - (microtime(true) * 1000000 - $this->iterationTime));

        if ($sleep_time < 100000) {
            $sleep_time = 100000;
        }

        usleep($sleep_time);
    }

    public function checkPromiseConditions(Promise $promise): void
    {
        $basePromise = $promise->getBasePromise();
        if ($basePromise->getTimeoutAt() <= Carbon::now()) {
            $basePromise->setState(StateEnum::TIMEOUT());
        } else {
            $conditions = $this->getConditionsForState($basePromise, $basePromise);
            $transition = $this->getTransitionForConditions($conditions, $basePromise);
            if ($transition) {
                $basePromise->setState($transition->getToState());
            }
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

        $conditions = $this->getConditionsForState($baseJob, $baseJob);
        $transition = $this->getTransitionForConditions($conditions, $basePromise);
        if ($transition) {
            $baseJob->setState($transition->getToState());
            PromiseJob::saveBaseJob($baseJob);
        }
    }
}

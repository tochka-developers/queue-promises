<?php

namespace Tochka\Promises\Core;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\WaitEvent;

class GarbageCollector
{
    private int $sleepTime;
    private int $deleteOlderThen;
    /** @var array<string> */
    private array $states;

    public function __construct(int $sleepTime, int $deleteOlderThen, array $states)
    {
        $this->sleepTime = $sleepTime;
        $this->deleteOlderThen = $deleteOlderThen;
        $this->states = $states;
    }

    /**
     * @codeCoverageIgnore
     */
    public function handle(): void
    {
        while (true) {
            $this->iteration();
            $this->sleep($this->sleepTime);
        }
    }

    public function iteration(): void
    {
        Promise::where('updated_at', '<=', Carbon::now()->subSeconds($this->deleteOlderThen))
            ->whereIn('state', $this->states)
            ->chunk(
                100,
                $this->getChunkHandleCallback()
            );
    }

    protected function getChunkHandleCallback(): callable
    {
        return function (Collection $promises) {
            /** @var array<Promise> $promises */
            foreach ($promises as $promise) {
                try {
                    DB::transaction(
                        function () use ($promise) {
                            $this->checkPromiseToDelete($promise->getBasePromise());
                        },
                        3
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        };
    }

    /**
     * @param int $sleepTime
     *
     * @codeCoverageIgnore
     */
    protected function sleep(int $sleepTime): void
    {
        sleep($sleepTime);
    }

    /**
     * @param \Tochka\Promises\Core\BasePromise $basePromise
     *
     * @throws \Exception
     */
    public function checkPromiseToDelete(BasePromise $basePromise): void
    {
        if ($this->checkHasParentPromise($basePromise)) {
            return;
        }

        /** @var array<PromiseJob> $jobs */
        $jobs = PromiseJob::byPromise($basePromise->getPromiseId())->get();
        foreach ($jobs as $job) {
            $this->checkJobsToDelete($job->getBaseJob());
        }

        $basePromise->getAttachedModel()->delete();
    }

    /**
     * Проверяем, если текущий промис является дочерним другого промиса, который не был удален, то пока не удаляем
     * текущий. Рано и поздно GC удалит родительский промис, и тогда можно будет грохнуть текущий
     *
     * @param \Tochka\Promises\Core\BasePromise $basePromise
     *
     * @return bool
     */
    public function checkHasParentPromise(BasePromise $basePromise): bool
    {
        $handler = $basePromise->getPromiseHandler();
        if ($handler->getBaseJobId() !== null) {
            $parentJob = PromiseJob::find($handler->getBaseJobId());
            if ($parentJob !== null) {
                $parentPromise = Promise::find($parentJob->getBaseJob()->getPromiseId());

                return $parentPromise !== null;
            }
        }

        return false;
    }

    /**
     * @param \Tochka\Promises\Core\BaseJob $baseJob
     *
     * @throws \Exception
     */
    public function checkJobsToDelete(BaseJob $baseJob): void
    {
        $handler = $baseJob->getInitialJob();

        if ($handler instanceof WaitEvent) {
            if ($handler->getAttachedModel() !== null) {
                $handler->getAttachedModel()->delete();
            } else {
                PromiseEvent::where('id', $handler->getId())->delete();
            }
        }

        $baseJob->getAttachedModel()->delete();
    }
}

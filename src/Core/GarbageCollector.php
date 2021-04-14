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
        Promise::whereIn('state', $this->states)
            ->chunkById(
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
                    $this->checkPromiseToDelete($promise->getBasePromise());
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            // дадим возможность поработать другим задачам
            $this->sleep(0.1);
        };
    }

    /**
     * @param int|float $seconds
     *
     * @codeCoverageIgnore
     */
    protected function sleep($seconds): void
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * @param \Tochka\Promises\Core\BasePromise $basePromise
     *
     * @throws \Exception
     */
    public function checkPromiseToDelete(BasePromise $basePromise): void
    {
        if (
            $basePromise->getUpdatedAt() > Carbon::now()->subSeconds($this->deleteOlderThen)
            || $this->checkHasParentPromise($basePromise)
        ) {
            return;
        }

        /** @var array<PromiseJob> $jobs */
        $jobIds = PromiseJob::byPromise($basePromise->getPromiseId())->get()->pluck('id')->all();
        PromiseJob::byPromise($basePromise->getPromiseId())->delete();
        PromiseEvent::whereIn('job_id', $jobIds)->delete();

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
}

<?php

namespace Tochka\Promises\Core;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tochka\Promises\Core\Support\DaemonWithSignals;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Models\PromiseJob;

class GarbageCollector
{
    use DaemonWithSignals;

    private int $sleepTime;
    private int $deleteOlderThen;
    /** @var array<string> */
    private array $states;
    private Carbon $lastIteration;

    public function __construct(int $sleepTime, int $deleteOlderThen, array $states)
    {
        $this->sleepTime = $sleepTime;
        $this->deleteOlderThen = $deleteOlderThen;
        $this->states = $states;
        $this->lastIteration = Carbon::parse(0);
    }

    /**
     * @codeCoverageIgnore
     */
    public function handle(): void
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            if ($this->shouldQuit()) {
                return;
            }

            if ($this->paused() || $this->sleepAfterLastIteration()) {
                $this->sleep(1);

                continue;
            }

            $this->iteration();

            $this->lastIteration = Carbon::now();
        }
    }

    public function iteration(): void
    {
        Promise::whereIn('state', $this->states)
            ->chunkById(
                2,
                $this->getChunkHandleCallback()
            );
    }

    protected function getChunkHandleCallback(): callable
    {
        return function (Collection $promises) {
            if ($this->paused() || $this->shouldQuit()) {
                return false;
            }

            /** @var array<Promise> $promises */
            foreach ($promises as $promise) {
                try {
                    $this->checkPromiseToDelete($promise->getBasePromise());
                } catch (IncorrectResolvingClass $e) {
                    /** @var array<PromiseJob> $jobs */
                    $jobIds = PromiseJob::byPromise($promise->id)->get()->pluck('id')->all();
                    PromiseJob::byPromise($promise->id)->delete();
                    PromiseEvent::whereIn('job_id', $jobIds)->delete();
                    $promise->delete();
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            // дадим возможность поработать другим задачам
            $this->sleep(0.1);

            return true;
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

    private function sleepAfterLastIteration(): bool
    {
        return $this->lastIteration > Carbon::now()->subSeconds($this->sleepTime);
    }
}

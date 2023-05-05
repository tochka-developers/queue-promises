<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Models\PromiseJob;

/**
 * @codeCoverageIgnore
 */
class PromiseJobRegistry
{
    public function load(int $id): BaseJob
    {
        $jobModel = PromiseJob::find($id);
        if (!$jobModel) {
            throw (new ModelNotFoundException())->setModel(PromiseJob::class, $id);
        }

        return $jobModel->getBaseJob();
    }

    /**
     * @param int $promise_id
     *
     * @return Collection<array-key, BaseJob>
     */
    public function loadByPromiseId(int $promise_id): Collection
    {
        /** @psalm-suppress InvalidTemplateParam */
        return PromiseJob::byPromise($promise_id)
            ->get()
            ->map(
                function (PromiseJob $jobModel): BaseJob {
                    return $jobModel->getBaseJob();
                }
            );
    }

    /**
     * @param int $promise_id
     *
     * @return \Illuminate\Support\LazyCollection|BaseJob[]
     */
    public function loadByPromiseIdCursor(int $promise_id): LazyCollection
    {
        return LazyCollection::make(
            function () use ($promise_id) {
                /** @var PromiseJob $job */
                foreach (PromiseJob::byPromise($promise_id)->cursor() as $job) {
                    yield $job->getBaseJob();
                }
            }
        );
    }

    /**
     * @param int      $promise_id
     * @param callable $callback
     * @param int      $chunk_size
     */
    public function loadByPromiseIdChunk(int $promise_id, callable $callback, int $chunk_size = 1000): void
    {
        PromiseJob::byPromise($promise_id)->chunk(
            $chunk_size,
            function ($jobs) use ($callback) {
                /** @var PromiseJob $job */
                foreach ($jobs as $job) {
                    $callback($job->getBaseJob());
                }
            }
        );
    }

    public function countByPromiseId(int $promise_id): int
    {
        return PromiseJob::byPromise($promise_id)->count();
    }

    /**
     * @param \Tochka\Promises\Core\BaseJob $job
     */
    public function save(BaseJob $job): void
    {
        PromiseJob::saveBaseJob($job);
    }

    public function deleteByPromiseId(int $promise_id): void
    {
        PromiseJob::byPromise($promise_id)->delete();
    }
}

<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Models\PromiseJob;

class PromiseJobRegistry implements PromiseJobRegistryInterface
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
        return PromiseJob::byPromise($promise_id)
            ->get()
            ->map(
                function (PromiseJob $jobModel): BaseJob {
                    return $jobModel->getBaseJob();
                },
            );
    }

    /**
     * @param int $promise_id
     *
     * @return LazyCollection<int, BaseJob>
     */
    public function loadByPromiseIdCursor(int $promise_id): LazyCollection
    {
        return LazyCollection::make(
            function () use ($promise_id) {
                /** @var PromiseJob $job */
                foreach (PromiseJob::byPromise($promise_id)->cursor() as $job) {
                    yield $job->getBaseJob();
                }
            },
        );
    }

    public function loadByPromiseIdChunk(int $promise_id, callable $callback, int $chunk_size = 1000): void
    {
        PromiseJob::byPromise($promise_id)->chunk(
            $chunk_size,
            function (Collection $jobs) use ($callback) {
                /** @var Collection<int, PromiseJob> $jobs */
                foreach ($jobs as $job) {
                    $callback($job->getBaseJob());
                }
            },
        );
    }

    public function countByPromiseId(int $promise_id): int
    {
        return PromiseJob::byPromise($promise_id)->count();
    }

    public function save(BaseJob $job): void
    {
        PromiseJob::saveBaseJob($job);
    }

    public function deleteByPromiseId(int $promise_id): void
    {
        PromiseJob::byPromise($promise_id)->delete();
    }
}

<?php

namespace Tochka\Promises\Registry;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Core\BaseJob;

/**
 * @api
 */
interface PromiseJobRegistryInterface
{
    public function load(int $id): BaseJob;

    /**
     * @return Collection<array-key, BaseJob>
     */
    public function loadByPromiseId(int $promise_id): Collection;

    /**
     * @return LazyCollection<int, BaseJob>
     */
    public function loadByPromiseIdCursor(int $promise_id): LazyCollection;

    /**
     * @param callable(BaseJob): void $callback
     */
    public function loadByPromiseIdChunk(int $promise_id, callable $callback, int $chunk_size = 1000): void;

    public function countByPromiseId(int $promise_id): int;

    public function save(BaseJob $job): void;

    public function deleteByPromiseId(int $promise_id): void;
}

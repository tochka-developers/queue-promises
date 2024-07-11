<?php

namespace Tochka\Promises\Registry;

use Illuminate\Support\LazyCollection;
use Tochka\Promises\Core\BasePromise;

/**
 * @api
 */
interface PromiseRegistryInterface
{
    public function load(int $id): BasePromise;

    /**
     * @return LazyCollection<int, BasePromise>
     */
    public function loadAllCursor(): LazyCollection;

    /**
     * @param callable(BasePromise): void $callback
     */
    public function loadAllChunk(callable $callback, int $chunk_size = 1000): void;

    /**
     * @param array $states
     * @return LazyCollection<int, BasePromise>
     */
    public function loadInStatesCursor(array $states): LazyCollection;

    /**
     * @param callable(BasePromise): void $callback
     */
    public function loadInStatesChunk(array $states, callable $callback, int $chunk_size = 1000): void;

    public function save(BasePromise $promise): void;

    public function delete(int $id): void;
}

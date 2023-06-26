<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Models\Promise;

/**
 * Связь BasePromise с сущностью в БД
 * @codeCoverageIgnore
 */
class PromiseRegistry
{
    public function load(int $id): BasePromise
    {
        /** @var Promise $promiseModel */
        $promiseModel = Promise::find($id);
        if (!$promiseModel) {
            throw (new ModelNotFoundException())->setModel(Promise::class, $id);
        }

        return $promiseModel->getBasePromise();
    }

    /**
     * @return LazyCollection<int, BasePromise>
     */
    public function loadAllCursor(): LazyCollection
    {
        return LazyCollection::make(
            function () {
                /** @var Promise $promise */
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                foreach (Promise::cursor() as $promise) {
                    yield $promise->getBasePromise();
                }
            }
        );
    }

    /**
     * @param callable $callback
     * @param int      $chunk_size
     */
    public function loadAllChunk(callable $callback, int $chunk_size = 1000): void
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Promise::chunk(
            $chunk_size,
            function ($promises) use ($callback) {
                /** @var Promise $promise */
                foreach ($promises as $promise) {
                    $callback($promise->getBasePromise());
                }
            }
        );
    }

    /**
     * @param array $states
     *
     * @return LazyCollection<int, BasePromise>
     */
    public function loadInStatesCursor(array $states): LazyCollection
    {
        return LazyCollection::make(
            function () use ($states) {
                /** @var Promise $promise */
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                foreach (Promise::whereIn('state', $states)->cursor() as $promise) {
                    yield $promise->getBasePromise();
                }
            }
        );
    }

    /**
     * @param array    $states
     * @param callable $callback
     * @param int      $chunk_size
     */
    public function loadInStatesChunk(array $states, callable $callback, int $chunk_size = 1000): void
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Promise::whereIn('state', $states)->chunk(
            $chunk_size,
            function ($promises) use ($callback) {
                /** @var Promise $promise */
                foreach ($promises as $promise) {
                    $callback($promise->getBasePromise());
                }
            }
        );
    }

    /**
     * @param BasePromise $promise
     */
    public function save(BasePromise $promise): void
    {
        Promise::saveBasePromise($promise);
    }

    /**
     * @param int $id
     *
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        Promise::where('id', $id)->delete();
    }
}

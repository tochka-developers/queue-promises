<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Models\Promise;

/**
 * Связь BasePromise с сущностью в БД
 */
class PromiseRegistry
{
    /**
     * @param int $id
     *
     * @return \Tochka\Promises\Core\BasePromise
     */
    public function load(int $id): BasePromise
    {
        /** @var Promise $promiseModel */
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $promiseModel = Promise::find($id);
        if (!$promiseModel) {
            throw (new ModelNotFoundException())->setModel(Promise::class, $id);
        }

        return $promiseModel->getBasePromise();
    }

    /**
     * @return \Illuminate\Support\LazyCollection|BasePromise[]
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
     * @return \Illuminate\Support\LazyCollection|BasePromise[]
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
     * @param \Tochka\Promises\Core\BasePromise $promise
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
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Promise::where('id', $id)->delete();
    }
}

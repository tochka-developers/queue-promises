<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Models\Promise;

/**
 * Связь BasePromise с сущностью в БД
 */
class PromiseRegistry implements PromiseRegistryInterface
{
    public function load(int $id): BasePromise
    {
        $promiseModel = Promise::find($id);
        if ($promiseModel === null) {
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
                foreach (Promise::query()->cursor() as $promise) {
                    yield $promise->getBasePromise();
                }
            },
        );
    }

    public function loadAllChunk(callable $callback, int $chunk_size = 1000): void
    {
        Promise::query()->chunk(
            $chunk_size,
            function (Collection $promises) use ($callback) {
                /** @var Collection<int, Promise> $promises */
                foreach ($promises as $promise) {
                    $callback($promise->getBasePromise());
                }
            },
        );
    }

    public function loadInStatesCursor(array $states): LazyCollection
    {
        return LazyCollection::make(
            function () use ($states) {
                foreach (Promise::whereIn('state', $states)->cursor() as $promise) {
                    yield $promise->getBasePromise();
                }
            },
        );
    }

    public function loadInStatesChunk(array $states, callable $callback, int $chunk_size = 1000): void
    {
        Promise::whereIn('state', $states)->chunk(
            $chunk_size,
            function (Collection $promises) use ($callback) {
                /** @var Collection<int, Promise> $promises */
                foreach ($promises as $promise) {
                    $callback($promise->getBasePromise());
                }
            },
        );
    }

    public function save(BasePromise $promise): void
    {
        Promise::saveBasePromise($promise);
    }

    public function delete(int $id): void
    {
        Promise::where('id', $id)->delete();
    }
}

<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;
use Tochka\Promises\Facades\Serializer;
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

        return $this->mapPromiseModel($promiseModel);
    }

    /**
     * @return \Illuminate\Support\LazyCollection|BasePromise[]
     */
    public function loadAllCursor(): LazyCollection
    {
        return LazyCollection::make(function () {
            /** @var Promise $promise */
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            foreach (Promise::cursor() as $promise) {
                yield $this->mapPromiseModel($promise);
            }
        });
    }

    /**
     * @return \Illuminate\Support\LazyCollection|BasePromise[]
     */
    public function loadInStatesCursor(array $states): LazyCollection
    {
        return LazyCollection::make(function () use ($states) {
            /** @var Promise $promise */
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            foreach (Promise::whereIn('state', $states)->cursor() as $promise) {
                yield $this->mapPromiseModel($promise);
            }
        });
    }

    /**
     * @param \Tochka\Promises\Core\BasePromise $promise
     */
    public function save(BasePromise $promise): void
    {
        $promiseModel = new Promise();
        $promiseId = $promise->getPromiseId();
        if ($promiseId !== null) {
            $promiseModel->id = $promiseId;
            $promiseModel->exists = true;
        } else {
            $promiseModel->exists = false;
        }

        $promiseModel->state = $promise->getState();
        $promiseModel->conditions = Serializer::getSerializedConditions($promise->getConditions());
        $promiseModel->promise_handler = Serializer::jsonSerialize(clone $promise->getPromiseHandler());

        $promiseModel->save();

        if ($promiseId === null) {
            $promise->setPromiseId($promiseModel->id);
        }
    }

    public function delete(int $id): void
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Promise::where('id', $id)->delete();
    }

    private function mapPromiseModel(Promise $promiseModel): BasePromise
    {
        $promiseHandler = Serializer::jsonUnSerialize($promiseModel->promise_handler);

        if (!$promiseHandler instanceof PromiseHandler) {
            throw new IncorrectResolvingClass(
                sprintf(
                    'Promise handler must implements contract [%s], but class [%s] is incorrect',
                    PromiseHandler::class,
                    get_class($promiseHandler)
                )
            );
        }

        $conditions = Serializer::getUnserializedConditions($promiseModel->conditions);

        $promise = new BasePromise($promiseHandler);
        $promise->setConditions($conditions);
        $promise->restoreState($promiseModel->state);
        $promise->setPromiseId($promiseModel->id);

        return $promise;
    }
}
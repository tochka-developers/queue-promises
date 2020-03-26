<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\LazyCollection;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;
use Tochka\Promises\Models\Promise;

/**
 * Связь BasePromise с сущностью в БД
 */
class PromiseRegistry
{
    use SerializeConditions;

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
     * @return \Illuminate\Support\LazyCollection
     */
    public function loadAllCursor(): LazyCollection
    {
        return LazyCollection::make(function () {
            /** @var Promise $promise */
            foreach (Promise::cursor() as $promise) {
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
        $promiseModel->conditions = $this->getSerializedConditions($promise->getConditions());
        $promiseModel->promise_handler = json_encode(
            serialize(clone $promise->getPromiseHandler()),
            JSON_THROW_ON_ERROR,
            512
        );

        $promiseModel->save();

        if ($promiseId === null) {
            $promise->setPromiseId($promiseModel->id);
        }
    }

    private function mapPromiseModel(Promise $promiseModel): BasePromise
    {
        $promiseHandler = unserialize(
            json_decode($promiseModel->promise_handler, true, 512, JSON_THROW_ON_ERROR),
            ['allowed_classes' => true]
        );
        if (!$promiseHandler instanceof PromiseHandler) {
            throw new IncorrectResolvingClass(
                sprintf(
                    'Promise handler must implements contract [%s], but class [%s] is incorrect',
                    PromiseHandler::class,
                    get_class($promiseHandler)
                )
            );
        }

        $conditions = $this->getUnserializedConditions($promiseModel->conditions);

        $promise = new BasePromise($promiseHandler);
        $promise->setConditions($conditions);
        $promise->restoreState($promiseModel->state);
        $promise->setPromiseId($promiseModel->id);

        return $promise;
    }
}
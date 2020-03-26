<?php

namespace Tochka\Promises\Registry;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tochka\Promises\BasePromise;
use Tochka\Promises\Contracts\PromiseHandler;
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
     * @return \Tochka\Promises\BasePromise
     */
    public function load(int $id): BasePromise
    {
        /** @var Promise $promiseModel */
        $promiseModel = Promise::find($id);
        if (!$promiseModel) {
            throw (new ModelNotFoundException())->setModel(Promise::class, $id);
        }

        $promiseHandler = unserialize($promiseModel->promise_handler, ['allowed_classes' => true]);
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

    /**
     * @param \Tochka\Promises\BasePromise $promise
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
        $promiseModel->promise_handler = serialize($promise->getPromiseHandler());

        $promiseModel->save();

        if ($promiseId === null) {
            $promise->setPromiseId($promiseModel->id);
        }
    }
}
<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\Support\ConditionTransitions;
use Tochka\Promises\Core\Support\States;
use Tochka\Promises\Core\Support\Time;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;

class BasePromise implements StatesContract, ConditionTransitionsContract
{
    use States, ConditionTransitions, Time;

    private ?int $id = null;
    private PromiseHandler $promiseHandler;
    private Promise $model;

    public function __construct(PromiseHandler $promiseHandler)
    {
        $this->promiseHandler = $promiseHandler;
        $this->state = StateEnum::WAITING();
        $this->model = new Promise();
    }

    public function getPromiseHandler(): PromiseHandler
    {
        return $this->promiseHandler;
    }

    public function getPromiseId(): ?int
    {
        return $this->id;
    }

    public function setPromiseId(int $id): void
    {
        $this->id = $id;
    }

    public function getAttachedModel(): Promise
    {
        return $this->model;
    }

    public function setAttachedModel(Promise $model): void
    {
        $this->model = $model;
    }

    public function dispatch(): void
    {
        $this->setState(StateEnum::RUNNING());

        Promise::saveBasePromise($this);
    }
}

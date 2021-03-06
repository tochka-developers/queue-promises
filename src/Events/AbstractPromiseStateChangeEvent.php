<?php

namespace Tochka\Promises\Events;

use Neves\Events\Contracts\TransactionalEvent;
use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;

abstract class AbstractPromiseStateChangeEvent implements StateChangedContract, TransactionalEvent
{
    private BasePromise $promise;
    private StateEnum $fromState;
    private StateEnum $toState;

    public function __construct(BasePromise $promise, StateEnum $fromState, StateEnum $toState)
    {
        $this->promise = $promise;
        $this->fromState = $fromState;
        $this->toState = $toState;
    }

    public function getPromise(): BasePromise
    {
        return $this->promise;
    }

    public function getFromState(): StateEnum
    {
        return $this->fromState;
    }

    public function getToState(): StateEnum
    {
        return $this->toState;
    }
}

<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;

abstract class AbstractPromiseJobStateChangeEvent
{
    private BaseJob $promise_job;
    private StateEnum $fromState;
    private StateEnum $toState;

    public function __construct(BaseJob $promise, StateEnum $fromState, StateEnum $toState)
    {
        $this->promise_job = $promise;
        $this->fromState = $fromState;
        $this->toState = $toState;
    }

    public function getPromiseJob(): BaseJob
    {
        return $this->promise_job;
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

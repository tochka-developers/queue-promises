<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;

abstract class AbstractPromiseJobStateChangeEvent implements StateChangedContract
{
    private BaseJob $promiseJob;
    private StateEnum $fromState;
    private StateEnum $toState;

    public function __construct(BaseJob $promiseJob, StateEnum $fromState, StateEnum $toState)
    {
        $this->promiseJob = $promiseJob;
        $this->fromState = $fromState;
        $this->toState = $toState;
    }

    public function getPromiseJob(): BaseJob
    {
        return $this->promiseJob;
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

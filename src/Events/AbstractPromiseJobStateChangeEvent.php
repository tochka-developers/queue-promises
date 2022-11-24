<?php

namespace Tochka\Promises\Events;

use Neves\Events\Contracts\TransactionalEvent;
use Tochka\Promises\Contracts\NestedEventContract;
use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;

abstract class AbstractPromiseJobStateChangeEvent implements
    StateChangedContract,
    TransactionalEvent,
    NestedEventContract
{
    private BaseJob $promiseJob;
    private StateEnum $fromState;
    private StateEnum $toState;
    private bool $nested;

    public function __construct(BaseJob $promiseJob, StateEnum $fromState, StateEnum $toState, bool $nested = false)
    {
        $this->promiseJob = $promiseJob;
        $this->fromState = $fromState;
        $this->toState = $toState;
        $this->nested = $nested;
    }

    public function isNested(): bool
    {
        return $this->nested;
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

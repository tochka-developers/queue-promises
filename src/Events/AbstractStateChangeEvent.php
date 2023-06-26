<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Enums\StateEnum;

abstract class AbstractStateChangeEvent implements StateChangedContract
{
    private StatesContract $instance;
    private StateEnum $fromState;
    private StateEnum $toState;

    public function __construct(StatesContract $instance, StateEnum $fromState, StateEnum $toState)
    {
        $this->instance = $instance;
        $this->fromState = $fromState;
        $this->toState = $toState;
    }

    public function getInstance(): StatesContract
    {
        return $this->instance;
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

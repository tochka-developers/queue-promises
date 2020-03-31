<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Enums\StateEnum;

class StateChanged
{
    /** @var \Tochka\Promises\Contracts\StatesContract */
    private $instance;
    /** @var \Tochka\Promises\Enums\StateEnum */
    private $from_state;
    /** @var \Tochka\Promises\Enums\StateEnum */
    private $to_state;

    public function __construct(StatesContract $instance, StateEnum $from_state, StateEnum $to_state)
    {
        $this->instance = $instance;
        $this->from_state = $from_state;
        $this->to_state = $to_state;
    }

    public function getInstance(): StatesContract
    {
        return $this->instance;
    }

    /**
     * @return \Tochka\Promises\Enums\StateEnum
     */
    public function getFromState(): StateEnum
    {
        return $this->from_state;
    }

    /**
     * @return \Tochka\Promises\Enums\StateEnum
     */
    public function getToState(): StateEnum
    {
        return $this->to_state;
    }
}
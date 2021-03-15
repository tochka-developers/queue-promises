<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;

abstract class AbstractPromiseJobStateChangeEvent
{
    /** @var \Tochka\Promises\Core\BaseJob */
    private $promise_job;
    /** @var \Tochka\Promises\Enums\StateEnum */
    private $from_state;
    /** @var \Tochka\Promises\Enums\StateEnum */
    private $to_state;

    public function __construct(BaseJob $promise, StateEnum $from_state, StateEnum $to_state)
    {
        $this->promise_job = $promise;
        $this->from_state = $from_state;
        $this->to_state = $to_state;
    }

    /**
     * @return \Tochka\Promises\Core\BaseJob
     */
    public function getPromiseJob(): BaseJob
    {
        return $this->promise_job;
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

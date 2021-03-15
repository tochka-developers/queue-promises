<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;

abstract class AbstractPromiseStateChangeEvent
{
    /** @var \Tochka\Promises\Core\BasePromise */
    private $promise;
    /** @var \Tochka\Promises\Enums\StateEnum */
    private $from_state;
    /** @var \Tochka\Promises\Enums\StateEnum */
    private $to_state;

    public function __construct(BasePromise $promise, StateEnum $from_state, StateEnum $to_state)
    {
        $this->promise = $promise;
        $this->from_state = $from_state;
        $this->to_state = $to_state;
    }

    /**
     * @return \Tochka\Promises\Core\BasePromise
     */
    public function getPromise(): BasePromise
    {
        return $this->promise;
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

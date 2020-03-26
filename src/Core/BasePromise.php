<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Core\Support\ConditionTransitions;
use Tochka\Promises\Core\Support\PromiseQueueJob;
use Tochka\Promises\Facades\PromiseRegistry;

/**
 * Class BasePromise
 *
 * @package App\Promises\Package
 */
class BasePromise implements States
{
    use FSM, ConditionTransitions;

    /** @var \Tochka\Promises\Contracts\PromiseHandler */
    private $promiseHandler;
    /** @var int|null */
    private $id = null;

    public function __construct(PromiseHandler $promiseHandler)
    {
        $this->promiseHandler = $promiseHandler;
        $this->state = self::WAITING;
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

    public function dispatch(): void
    {
        $this->setState(self::RUNNING);

        PromiseRegistry::save($this);
    }

    public function transitionFromRunningToSuccess(): void
    {
        dispatch(new PromiseQueueJob($this->promiseHandler, 'success'));

        PromiseRegistry::save($this);
    }

    public function transitionFromRunningToFailed(): void
    {
        dispatch(new PromiseQueueJob($this->promiseHandler, 'failed'));

        PromiseRegistry::save($this);
    }

    public function transitionFromRunningToTimeout(): void
    {
        dispatch(new PromiseQueueJob($this->promiseHandler, 'timeout'));

        PromiseRegistry::save($this);
    }
}

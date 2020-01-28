<?php

namespace Tochka\Promises;

use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Support\Database;

/**
 * Class BasePromise
 *
 * @package App\Promises\Package
 */
class BasePromise implements States
{
    use FSM, ConditionTransitions, Database;

    /** @var \Tochka\Promises\Contracts\PromiseHandler */
    private $promiseHandler;

    public function __construct(PromiseHandler $promiseHandler)
    {
        $this->promiseHandler = $promiseHandler;
        $this->state = self::WAITING;

        $this->save();
    }

    public function getPromiseHandler(): PromiseHandler
    {
        return $this->promiseHandler;
    }

    /**
     * @return \App\Promises\Package\Contracts\MayPromised[]
     */
    public function getJobs(): array
    {

    }

    public function getJob(int $id): BaseJob
    {

    }


    public function dispatch(): void
    {
        $this->setState(self::RUNNING);
    }

    public function transitionFromRunningToSuccess(): void
    {
        dispatch(new PromiseQueueJob($this->promiseHandler, 'success'));

        $this->save();
    }

    public function transitionFromRunningToFailed(): void
    {
        dispatch(new PromiseQueueJob($this->promiseHandler, 'failed'));

        $this->save();
    }

    public function transitionFromRunningToTimeout(): void
    {
        dispatch(new PromiseQueueJob($this->promiseHandler, 'timeout'));

        $this->save();
    }
}

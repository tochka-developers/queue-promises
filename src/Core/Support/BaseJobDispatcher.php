<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;

class BaseJobDispatcher
{
    /** @var DispatcherContract[] */
    private $dispatchers = [];

    /**
     * @param \Tochka\Promises\Contracts\DispatcherContract $dispatcher
     */
    public function addDispatcher(DispatcherContract $dispatcher): void
    {
        $this->dispatchers[get_class($dispatcher)] = $dispatcher;
    }

    /**
     * @param \Tochka\Promises\Contracts\MayPromised $job
     */
    public function dispatch(MayPromised $job): void
    {
        foreach ($this->dispatchers as $dispatcher) {
            if ($dispatcher->mayDispatch($job)) {
                $dispatcher->dispatch($job);
            }
        }
    }
}
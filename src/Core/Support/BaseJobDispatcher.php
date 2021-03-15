<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;

class BaseJobDispatcher
{
    /** @var array<DispatcherContract> */
    private array $dispatchers = [];

    public function addDispatcher(DispatcherContract $dispatcher): void
    {
        $this->dispatchers[get_class($dispatcher)] = $dispatcher;
    }

    public function dispatch(MayPromised $job): void
    {
        foreach ($this->dispatchers as $dispatcher) {
            if ($dispatcher->mayDispatch($job)) {
                $dispatcher->dispatch($job);
            }
        }
    }
}

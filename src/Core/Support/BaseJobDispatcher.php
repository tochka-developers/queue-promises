<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Support\Facades\Event;
use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Events\PromiseJobStarted;
use Tochka\Promises\Events\PromiseJobStarting;

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
        Event::dispatch(new PromiseJobStarting($job));

        foreach ($this->dispatchers as $dispatcher) {
            if ($dispatcher->mayDispatch($job)) {
                $dispatcher->dispatch($job);
            }
        }

        Event::dispatch(new PromiseJobStarted($job));
    }
}

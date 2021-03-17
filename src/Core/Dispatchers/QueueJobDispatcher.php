<?php

namespace Tochka\Promises\Core\Dispatchers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;

class QueueJobDispatcher implements DispatcherContract
{
    public function mayDispatch(MayPromised $promised): bool
    {
        return $promised instanceof ShouldQueue;
    }

    /**
     * @param \Tochka\Promises\Contracts\MayPromised $promised
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function dispatch(MayPromised $promised): void
    {
        $dispatcher = Container::getInstance()->make(Dispatcher::class);
        $dispatcher->dispatch($promised);
    }
}

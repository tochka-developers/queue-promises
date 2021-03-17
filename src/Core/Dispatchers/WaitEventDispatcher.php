<?php

namespace Tochka\Promises\Core\Dispatchers;

use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Support\WaitEvent;

class WaitEventDispatcher implements DispatcherContract
{
    public function mayDispatch(MayPromised $promised): bool
    {
        return $promised instanceof WaitEvent;
    }

    public function dispatch(MayPromised $promised): void
    {
        /** @var WaitEvent $promised */
        PromiseEvent::saveWaitEvent($promised);
    }
}

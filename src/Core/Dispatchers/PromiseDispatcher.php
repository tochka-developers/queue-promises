<?php

namespace Tochka\Promises\Core\Dispatchers;

use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;

class PromiseDispatcher implements DispatcherContract
{
    public function mayDispatch(MayPromised $promised): bool
    {
        return $promised instanceof PromiseHandler;
    }

    public function dispatch(MayPromised $promised): void
    {
        /** @var PromiseHandler $promised */
        $promised->run();
    }
}

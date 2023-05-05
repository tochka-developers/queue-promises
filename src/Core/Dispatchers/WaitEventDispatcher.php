<?php

namespace Tochka\Promises\Core\Dispatchers;

use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Models\PromiseJob;
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

        // Чтобы сохранить ID события в задачу-обертку
        $promiseJob = PromiseJob::find($promised->getBaseJobId());
        if ($promiseJob !== null) {
            $promiseJob->initial_job = $promised;
            $promiseJob->save();
        }
    }
}

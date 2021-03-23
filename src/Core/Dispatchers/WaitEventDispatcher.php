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
        $promise_job = PromiseJob::find($promised->getBaseJobId());
        if ($promise_job !== null) {
            $promise_job->initial_job = $promised;
            $promise_job->save();
        }
    }
}

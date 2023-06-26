<?php

namespace Tochka\Promises\Models\Observers;

use Illuminate\Support\Facades\Event;
use Tochka\Promises\Events\PromiseJobStateChanging;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Models\PromiseJob;

class PromiseJobBeforeCommitObserver
{
    public function updating(PromiseJob $promiseJob): void
    {
        if ($promiseJob->isDirty('state')) {
            $oldState = $promiseJob->getChangedState();
            $currentState = $promiseJob->state;

            Event::dispatch(new StateChanging($promiseJob->getBaseJob(), $oldState, $currentState));
            Event::dispatch(
                new PromiseJobStateChanging(
                    $promiseJob->getBaseJob(),
                    $oldState,
                    $currentState,
                    $promiseJob->isNestedEvents()
                )
            );
        }
    }
}

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
        $oldState = $promiseJob->getChangedState() ?? $promiseJob->state;

        if ($promiseJob->state->isNot($oldState)) {
            Event::dispatch(new StateChanging($promiseJob->getBaseJob(), $oldState, $promiseJob->state));
            Event::dispatch(
                new PromiseJobStateChanging(
                    $promiseJob->getBaseJob(),
                    $oldState,
                    $promiseJob->state,
                    $promiseJob->isNestedEvents()
                )
            );
        }
    }
}

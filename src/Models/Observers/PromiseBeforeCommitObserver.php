<?php

namespace Tochka\Promises\Models\Observers;

use Illuminate\Support\Facades\Event;
use Tochka\Promises\Events\PromiseStateChanging;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Models\Promise;

class PromiseBeforeCommitObserver
{
    public function updating(Promise $promise): void
    {
        if ($promise->isDirty('state')) {
            $oldState = $promise->getChangedState();
            $currentState = $promise->state;

            Event::dispatch(
                new StateChanging($promise->getBasePromise(), $oldState, $currentState)
            );
            Event::dispatch(
                new PromiseStateChanging(
                    $promise->getBasePromise(),
                    $oldState,
                    $currentState,
                    $promise->isNestedEvents()
                )
            );
        }
    }
}

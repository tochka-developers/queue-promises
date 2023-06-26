<?php

namespace Tochka\Promises\Models\Observers;

use Illuminate\Support\Facades\Event;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Models\Promise;

class PromiseAfterCommitObserver
{
    public bool $afterCommit = true;

    public function updated(Promise $promise): void
    {
        if ($promise->getChangedState() !== $promise->state) {
            $oldState = $promise->getChangedState();
            $currentState = $promise->state;

            Event::dispatch(
                new StateChanged($promise->getBasePromise(), $oldState, $currentState)
            );
            Event::dispatch(
                new PromiseStateChanged(
                    $promise->getBasePromise(),
                    $oldState,
                    $currentState,
                    $promise->isNestedEvents()
                )
            );
        }
    }
}

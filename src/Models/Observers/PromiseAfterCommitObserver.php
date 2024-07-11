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
        $oldState = $promise->getChangedState() ?? $promise->state;

        if ($promise->state->isNot($oldState)) {
            Event::dispatch(new StateChanged($promise->getBasePromise(), $oldState, $promise->state));
            Event::dispatch(
                new PromiseStateChanged(
                    $promise->getBasePromise(),
                    $oldState,
                    $promise->state,
                    $promise->isNestedEvents(),
                ),
            );
        }
    }
}

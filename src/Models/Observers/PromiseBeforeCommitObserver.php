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
        $oldState = $promise->getChangedState() ?? $promise->state;

        if ($oldState !== $promise->state) {
            Event::dispatch(new StateChanging($promise->getBasePromise(), $oldState, $promise->state));
            Event::dispatch(
                new PromiseStateChanging(
                    $promise->getBasePromise(),
                    $oldState,
                    $promise->state,
                    $promise->isNestedEvents()
                )
            );
        }
    }
}

<?php

namespace Tochka\Promises\Models\Observers;

use Illuminate\Support\Facades\Event;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Events\PromiseStateChanging;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Models\Promise;

class PromiseObserver
{
    public function saving(Promise $promise): void
    {
        if ($promise->wasChanged('state')) {
            $old_state = $promise->getOriginal('state');
            $current_state = $promise->state;
            Event::dispatch(new StateChanging($promise->getBasePromise(), $old_state, $current_state));
            Event::dispatch(new PromiseStateChanging($promise->getBasePromise(), $old_state, $current_state));
        }
    }

    public function saved(Promise $promise): void
    {
        if ($promise->wasChanged('state')) {
            $old_state = $promise->getOriginal('state');
            $current_state = $promise->state;
            Event::dispatch(new StateChanged($promise->getBasePromise(), $old_state, $current_state));
            Event::dispatch(new PromiseStateChanged($promise->getBasePromise(), $old_state, $current_state));
        }
    }
}

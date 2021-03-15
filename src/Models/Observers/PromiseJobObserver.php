<?php

namespace Tochka\Promises\Models\Observers;

use Illuminate\Support\Facades\Event;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseJobStateChanging;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Models\PromiseJob;

class PromiseJobObserver
{
    public function saving(PromiseJob $promise): void
    {
        if ($promise->wasChanged('state')) {
            $old_state = $promise->getOriginal('state');
            $current_state = $promise->state;
            Event::dispatch(new StateChanging($promise->getBaseJob(), $old_state, $current_state));
            Event::dispatch(new PromiseJobStateChanging($promise->getBaseJob(), $old_state, $current_state));
        }
    }

    public function saved(PromiseJob $promise): void
    {
        if ($promise->wasChanged('state')) {
            $old_state = $promise->getOriginal('state');
            $current_state = $promise->state;
            Event::dispatch(new StateChanged($promise->getBaseJob(), $old_state, $current_state));
            Event::dispatch(new PromiseJobStateChanged($promise->getBaseJob(), $old_state, $current_state));
        }
    }
}

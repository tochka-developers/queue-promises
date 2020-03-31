<?php

namespace Tochka\Promises\Listeners;

use Illuminate\Support\Facades\Event;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Events\StateChanged;

class FilterStateChanged
{
    public function handle(StateChanged $event): void
    {
        $instance = $event->getInstance();
        switch (get_class($instance)) {
            case BasePromise::class:
                /** @var BasePromise $instance */
                Event::dispatch(new PromiseStateChanged($instance, $event->getFromState(), $event->getToState()));
                break;
            case BaseJob::class:
                /** @var BaseJob $instance */
                Event::dispatch(new PromiseJobStateChanged($instance, $event->getFromState(), $event->getToState()));
                break;
        }
    }
}
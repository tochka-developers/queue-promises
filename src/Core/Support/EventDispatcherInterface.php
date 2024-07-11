<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Support\WaitEvent;

interface EventDispatcherInterface
{
    public function dispatch(PromisedEvent $event): void;

    public function updateEventState(PromisedEvent $event, WaitEvent $waitEvent): void;
}

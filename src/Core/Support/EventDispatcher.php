<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Support\Facades\DB;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseEventRegistry;
use Tochka\Promises\Facades\PromiseJobRegistry;

class EventDispatcher
{
    public function dispatch(PromisedEvent $event): void
    {
        $waitEvents = PromiseEventRegistry::loadByEvent(get_class($event), $event->getUniqueId());

        if (!$waitEvents->count()) {
            return;
        }

        foreach ($waitEvents as $waitEvent) {
            DB::transaction(static function () use ($waitEvent) {
                $baseJob = PromiseJobRegistry::load($waitEvent->getBaseJobId());
                $baseJob->setState(StateEnum::SUCCESS());
                PromiseJobRegistry::save($baseJob);
                PromiseEventRegistry::delete($waitEvent->getId());
            });
        }
    }
}
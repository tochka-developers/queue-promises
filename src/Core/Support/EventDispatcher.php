<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Support\Facades\DB;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\WaitEvent;

class EventDispatcher
{
    public function dispatch(PromisedEvent $event): void
    {
        $promiseEvents = PromiseEvent::byEvent(get_class($event), $event->getUniqueId())->lock()->get();

        if (!$promiseEvents->count()) {
            return;
        }

        /** @var PromiseEvent $promiseEvent */
        foreach ($promiseEvents as $promiseEvent) {
            $this->updateEventState($event, $promiseEvent->getWaitEvent());
        }
    }

    public function updateEventState(PromisedEvent $event, WaitEvent $waitEvent): void
    {
        DB::transaction(
            function () use ($event, $waitEvent) {
                $cloneWaitEvent = clone $waitEvent;

                $job = PromiseJob::lockForUpdate()->find($cloneWaitEvent->getBaseJobId());
                if ($job !== null) {
                    $cloneWaitEvent->setEvent($event);
                    $cloneWaitEvent->setAttachedModel(null);

                    $baseJob = $job->getBaseJob();
                    $baseJob->setState(StateEnum::SUCCESS());
                    $baseJob->setResult($cloneWaitEvent);

                    PromiseJob::saveBaseJob($baseJob);
                }
            },
            3
        );
    }
}

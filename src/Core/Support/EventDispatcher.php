<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Support\Facades\DB;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Models\PromiseJob;

class EventDispatcher
{
    public function dispatch(PromisedEvent $event): void
    {
        DB::transaction(
            function () use ($event) {
                $promiseEvents = PromiseEvent::byEvent(get_class($event), $event->getUniqueId())->lock()->get();

                if (!$promiseEvents->count()) {
                    return;
                }

                /** @var PromiseEvent $promiseEvent */
                foreach ($promiseEvents as $promiseEvent) {
                    $waitEvent = clone $promiseEvent->getWaitEvent();

                    $job = PromiseJob::find($waitEvent->getBaseJobId());
                    if ($job !== null) {
                        $waitEvent->setEvent($event);
                        $waitEvent->setAttachedModel(null);

                        $baseJob = $job->getBaseJob();
                        $baseJob->setState(StateEnum::SUCCESS());
                        $baseJob->setResult($waitEvent);

                        PromiseJob::saveBaseJob($baseJob);
                    }

                    $promiseEvent->delete();
                }
            },
            3
        );
    }
}

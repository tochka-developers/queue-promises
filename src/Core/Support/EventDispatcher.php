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
        $promiseEvents = PromiseEvent::byEvent(get_class($event), $event->getUniqueId())->get();

        if (!$promiseEvents->count()) {
            return;
        }

        /** @var PromiseEvent $promiseEvent */
        foreach ($promiseEvents as $promiseEvent) {
            DB::transaction(
                function () use ($event, $promiseEvent) {
                    $waitEvent = $promiseEvent->getWaitEvent();

                    $job = PromiseJob::find($waitEvent->getBaseJobId());
                    if ($job === null) {
                        return;
                    }

                    $baseJob = $job->getBaseJob();
                    $baseJob->setState(StateEnum::SUCCESS());
                    $baseJob->setResult($event);

                    PromiseJob::saveBaseJob($baseJob);
                    if ($waitEvent->getAttachedModel() !== null) {
                        $waitEvent->getAttachedModel()->delete();
                    } else {
                        PromiseEvent::where('id', $waitEvent->getId())->delete();
                    }
                }
            );
        }
    }
}

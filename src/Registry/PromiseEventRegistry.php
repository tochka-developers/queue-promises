<?php

namespace Tochka\Promises\Registry;

use Illuminate\Support\Collection;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Support\WaitEvent;

/**
 * Связь WaitEvent с сущностью в БД
 */
class PromiseEventRegistry
{
    /**
     * @param string $event_name
     * @param string $event_unique_id
     *
     * @return \Tochka\Promises\Support\WaitEvent[]|Collection
     */
    public function loadByEvent(string $event_name, string $event_unique_id): Collection
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return PromiseEvent::where('event_name', $event_name)
            ->where('event_unique_id', $event_unique_id)
            ->get()
            ->map(function ($promiseEventModel) {
                return $this->mapPromiseEventModel($promiseEventModel);
            });
    }

    /**
     * @param \Tochka\Promises\Support\WaitEvent $waitEvent
     */
    public function save(WaitEvent $waitEvent): void
    {
        $promiseEventModel = new PromiseEvent();

        $eventId = $waitEvent->getId();
        if ($eventId !== null) {
            $promiseEventModel->id = $eventId;
            $promiseEventModel->exists = true;
        } else {
            $promiseEventModel->exists = false;
        }

        $promiseEventModel->job_id = $waitEvent->getBaseJobId();
        $promiseEventModel->event_name = $waitEvent->getEventName();
        $promiseEventModel->event_unique_id = $waitEvent->getEventUniqueId();

        $promiseEventModel->save();

        if ($eventId === null) {
            $waitEvent->setId($promiseEventModel->id);
        }
    }

    public function delete(int $id): void
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        PromiseEvent::where('id', $id)
            ->delete();
    }

    private function mapPromiseEventModel(PromiseEvent $promiseEventModel): WaitEvent
    {
        $waitEvent = new WaitEvent($promiseEventModel->event_name, $promiseEventModel->event_unique_id);
        $waitEvent->setBaseJobId($promiseEventModel->job_id);
        $waitEvent->setId($promiseEventModel->id);

        return $waitEvent;
    }
}
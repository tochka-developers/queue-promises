<?php

namespace Tochka\Promises\Registry;

use Illuminate\Support\Collection;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Support\WaitEvent;

/**
 * Связь WaitEvent с сущностью в БД
 * @codeCoverageIgnore
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
        return PromiseEvent::byEvent($event_name, $event_unique_id)
            ->get()
            ->map(
                function (PromiseEvent $promiseEventModel) {
                    return $promiseEventModel->getWaitEvent();
                }
            );
    }

    /**
     * @param \Tochka\Promises\Support\WaitEvent $waitEvent
     */
    public function save(WaitEvent $waitEvent): void
    {
        PromiseEvent::saveWaitEvent($waitEvent);
    }

    /**
     * @param int $id
     *
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        PromiseEvent::where('id', $id)
            ->delete();
    }
}

<?php

namespace Tochka\Promises\Registry;

use Illuminate\Support\Collection;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Support\WaitEvent;

/**
 * Связь WaitEvent с сущностью в БД
 * @codeCoverageIgnore
 */
class PromiseEventRegistry implements PromiseEventRegistryInterface
{
    /**
     * @param string $event_name
     * @param string $event_unique_id
     * @return Collection<array-key, WaitEvent>
     */
    public function loadByEvent(string $event_name, string $event_unique_id): Collection
    {
        return PromiseEvent::byEvent($event_name, $event_unique_id)
            ->get()
            ->map(
                function (PromiseEvent $promiseEventModel): WaitEvent {
                    return $promiseEventModel->getWaitEvent();
                },
            );
    }

    public function save(WaitEvent $waitEvent): void
    {
        PromiseEvent::saveWaitEvent($waitEvent);
    }

    /**
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        PromiseEvent::where('id', $id)
            ->delete();
    }
}

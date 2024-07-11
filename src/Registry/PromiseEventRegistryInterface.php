<?php

namespace Tochka\Promises\Registry;

use Illuminate\Support\Collection;
use Tochka\Promises\Support\WaitEvent;

/**
 * @api
 */
interface PromiseEventRegistryInterface
{
    /**
     * @param string $event_name
     * @param string $event_unique_id
     * @return Collection<array-key, WaitEvent>
     */
    public function loadByEvent(string $event_name, string $event_unique_id): Collection;

    public function save(WaitEvent $waitEvent): void;

    public function delete(int $id): void;
}

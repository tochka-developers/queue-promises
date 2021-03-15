<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Models\PromiseEvent;

class WaitEvent implements MayPromised
{
    use BaseJobId;

    private ?int $id = null;
    private string $event_name;
    private string $event_unique_id;
    private PromiseEvent $model;

    public function __construct(string $event_name, string $event_unique_id)
    {
        $this->event_name = $event_name;
        $this->event_unique_id = $event_unique_id;
        $this->model = new PromiseEvent();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return $this->event_name;
    }

    public function getEventUniqueId(): string
    {
        return $this->event_unique_id;
    }

    public function getAttachedModel(): PromiseEvent
    {
        return $this->model;
    }

    public function setAttachedModel(PromiseEvent $model): void
    {
        $this->model = $model;
    }
}

<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Contracts\MayPromised;

class WaitEvent implements MayPromised
{
    use BaseJobId;

    /** @var int */
    private $id;
    /** @var string */
    private $event_name;
    /** @var string */
    private $event_unique_id;

    public function __construct(string $event_name, string $event_unique_id)
    {
        $this->event_name = $event_name;
        $this->event_unique_id = $event_unique_id;
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
}
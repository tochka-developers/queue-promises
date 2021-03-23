<?php

namespace Tochka\Promises\Tests\TestHelpers;

use Tochka\Promises\Contracts\PromisedEvent;

class TestEvent implements PromisedEvent
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getUniqueId(): string
    {
        return $this->id;
    }
}

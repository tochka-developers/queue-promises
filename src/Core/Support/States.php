<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Enums\StateEnum;

trait States
{
    /** @var StateEnum */
    private $state;

    public function getState(): StateEnum
    {
        return $this->state;
    }

    public function setState(StateEnum $state): void
    {
        $this->state = $state;
    }
}

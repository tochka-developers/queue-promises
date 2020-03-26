<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Enums\StateEnum;

interface StatesContract
{
    /**
     * @return StateEnum
     */
    public function getState(): ?StateEnum;

    /**
     * @param StateEnum $state
     */
    public function setState(StateEnum $state): void;
}
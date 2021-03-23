<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Enums\StateEnum;

/**
 * @codeCoverageIgnore
 */
interface StatesContract
{
    public function getState(): StateEnum;

    public function setState(StateEnum $state): void;
}

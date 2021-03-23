<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Enums\StateEnum;

/**
 * @codeCoverageIgnore
 */
interface JobStateContract
{
    public function getState(): StateEnum;
}

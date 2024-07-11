<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Enums\StateEnum;

/**
 * @api
 */
interface JobStateContract
{
    public function getState(): StateEnum;
}
